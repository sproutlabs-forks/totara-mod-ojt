<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ojt\local;

use DateTime;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();

class mod_ojt_plugin {

    /*
     * Class constants
     */

    /**
     * @const string    Reduce chance of typos.
     */
    const PLUGIN_NAME = 'mod_ojt';

    const SECTION_POSITION = 0;

    /**
     * @const string    Form id for filepicker form element.
     */
    const FORMID_FILES = 'filepicker';

    /**
     * @var array
     */
    private static $user_id_field_options = null;


    /*
     * Methods
     */

    /**
     * Return list of valid options for user record field matching
     *
     * @return array
     */
    public static function get_user_id_field_options()
    {

        if (self::$user_id_field_options == null) {
            self::$user_id_field_options = array(
                'username' => get_string('username'),
                'email' => get_string('email'),
                'idnumber' => get_string('idnumber')
            );
        }

        return self::$user_id_field_options;
    }

    public static function import_topic_file(stdClass $course, stored_file $import_file)
    {
        global $DB;
        // Default return value
        $result = '';
        // Open and fetch the file contents
        $fh = $import_file->get_content_file_handle();

        $csv = array();
        $i = 0;
        if ($fh) {
            $columns = fgetcsv($fh, '', ",");
            while (($row = fgetcsv($fh, '', ",")) !== false) {
                $csv[$i] = array_combine($columns, $row);
                $i++;
            }
            fclose($fh);
        }


        $temptopics = array_unique(array_column($csv, 'ojt_topic'));
        $topics = array_intersect_key($csv, $temptopics);

        $section = course_add_section($course, self::SECTION_POSITION);

        if (empty($section->id)) {
            $section = $DB->get_record('course_sections', array('course' => $course->id, 'section' => self::SECTION_POSITION));
        }
        foreach ($topics as $csvTopicRow) {

            self::create_ojts($course, $csvTopicRow, $section, $csv);
        }


        return (empty($result)) ? get_string('importsuccess', self::PLUGIN_NAME) : $result;
    } // import_file

    public static function create_ojts($course, $csvTopicRow, $section, $csv)
    {
        global $DB, $CFG;
        $topicName = $csvTopicRow['ojt_topic'];
        $ojt = $DB->get_record('ojt', array('course' => $course->id, 'name' => $topicName));
        $topic = new stdClass();
        $topic->name = $topicName;
        $topic->course = $course->id;
        $topic->intro = '';
        $topic->completiontopics = 1;
        $topic->timemodified = time();

        if (empty($ojt->id)) {
            $topic->timecreated = time();
            $ojt = $DB->insert_record('ojt', $topic);
        } else {
            $topic->id = $ojt->id;
            $DB->update_record('ojt', $topic);
        }

        $cmid = self::create_course_modules($course, $ojt, $section);
        $ojtTopicRecord = self::create_ojt_topics($ojt, $csvTopicRow);
        self::update_course_sections($course, $section, $cmid);

        self::create_ojt_topic_tasks($csv, $csvTopicRow, $ojtTopicRecord);
    }

    public static function create_course_modules($course, $ojt, $section)
    {


        global $DB, $CFG;
        $cm = new stdClass();
        $cm->course = $course->id;
        $cm->module = $DB->get_field('modules', 'id', array('name' => 'ojt'));
        $ojtid = (is_object($ojt)) ? $ojt->id : (int)$ojt;
        $cm->instance = $ojtid;
        $cm->section = $section->id;
        $cm->idnumber = 0;
        $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
        $cmid = $DB->get_record('course_modules', array('course' => $course->id, 'section' => $cm->section, 'instance' => $cm->instance, 'module' => $cm->module));
        if (empty($cmid->id)) {
            $cm->added = time();
            $cmid = $DB->insert_record('course_modules', $cm);
        } else {
            $cm->id = $cmid->id;
            $DB->update_record('course_modules', $cm);
        }

        return $cmid;
    }

    public static function create_ojt_topics($ojt, $csvTopicRow)
    {
        global $DB, $CFG;
        $ojtid = (is_object($ojt)) ? $ojt->id : (int)$ojt;
        $topicName = $csvTopicRow['ojt_topic'];
        $ojtTopicRecord = $DB->get_record('ojt_topic', array('ojtid' => $ojtid, 'name' => $topicName));
        $ojtTopic = new stdClass();
        $ojtTopic->name = $topicName;
        $ojtTopic->ojtid = $ojtid;
        if (isset($csvTopicRow['ojt_topic_completion_optional'])) {
            $ojtTopic->completionreq = $csvTopicRow['ojt_topic_completion_optional'];
        }

        if (empty($ojtTopicRecord->id)) {
            $ojtTopicRecord = $DB->insert_record('ojt_topic', $ojtTopic);
        } else {
            $ojtTopic->id = $ojtTopicRecord->id;
            $DB->update_record('ojt_topic', $ojtTopic);
        }

        return $ojtTopicRecord;
    }

    public static function update_course_sections($course, $section, $cmid)
    {

        global $DB, $CFG;
        $section = $DB->get_record('course_sections', array('id' => $section->id));
        $course_module_id = (is_object($cmid)) ? $cmid->id : (int)$cmid;
        $sectionData = new stdClass();
        $sectionData->sequence = "{$section->sequence},$course_module_id";
        course_update_section($course, $section, $sectionData);
    }

    public static function create_ojt_topic_tasks($csv, $csvTopicRow, $ojtTopicRecord)
    {

        global $DB, $CFG;

        $topicName = $csvTopicRow['ojt_topic'];
        $ojtTopicItems = array_filter($csv, function ($i) use ($topicName) {
            return $i['ojt_topic'] == $topicName;
        });

        $topicid = (is_object($ojtTopicRecord)) ? $ojtTopicRecord->id : (int)$ojtTopicRecord;
        foreach ($ojtTopicItems as $ojtTopicItem) {
            $topicItem = $DB->get_record('ojt_topic_item', array('topicid' => $topicid, 'name' => $ojtTopicItem['ojt_task']));
            $tempTopicItem = new stdClass();
            $tempTopicItem->name = $ojtTopicItem['ojt_task'];
            $tempTopicItem->topicid = $topicid;
            if (isset($ojtTopicItem['ojt_task_completion_optional'])) {
                $tempTopicItem->completionreq = $ojtTopicItem['ojt_task_completion_optional'];;
            }
            $tempTopicItem->allowfileuploads = 1;
            $tempTopicItem->allowselffileuploads = 1;
            if (isset($ojtTopicItem['ojt_task_fileuploads'])) {
                $tempTopicItem->allowfileuploads = $ojtTopicItem['ojt_task_fileuploads'];;
            }
            if (isset($ojtTopicItem['ojt_task_selffileuploads'])) {
                $tempTopicItem->allowselffileuploads = $ojtTopicItem['ojt_task_selffileuploads'];;
            }


            if ($topicItem) {
                $tempTopicItem->id = $topicItem->id;
                $DB->update_record('ojt_topic_item', $tempTopicItem);
            } else {
                $topicItem = $DB->insert_record('ojt_topic_item', $tempTopicItem);
            }
        }
    }


    public static function import_completion_file(stdClass $course, stored_file $import_file)
    {
        global $DB, $USER;
        // Default return value
        $result = '';
        // Open and fetch the file contents
        $fh = $import_file->get_content_file_handle();

        $csv = array();
        $i = 0;
        if ($fh) {
            $columns = fgetcsv($fh, '', ",");
            while (($row = fgetcsv($fh, '', ",")) !== false) {
                $csv[$i] = array_combine($columns, $row);
                $i++;
            }
            fclose($fh);
        }
        self::update_ojt_topic($course, $csv);

        return (empty($result)) ? get_string('importsuccess', self::PLUGIN_NAME) : $result;
    }


    public static function get_user_ids_and_enrol($course, $csv)
    {

        global $DB, $USER;
        $enrolinstances = enrol_get_instances($course->id, true);
        $enrol = enrol_get_plugin('manual');
        $instance = '';
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }

        $uniqueUsers = array_unique(array_map(function ($value) {
            return $value['username'];
        }, $csv));

        $usersarray = [];
        foreach ($uniqueUsers as $user) {
            $userobject = $DB->get_record('user', array('username' => $user));
            if ($userobject) {
                $usersarray[$user] = $userobject->id;
            } else {
                $userobject = $DB->get_record('user', array('idnumber' => $user));
                if ($userobject) {
                    $usersarray[$user] = $userobject->id;
                }
            }
            if ($userobject && $instance) {
                $enrol->enrol_user($instance, $userobject->id, 5);
            }
        }

        return $usersarray;
    }

    public static function get_topic_items($ojtTopics, $ojtTopicObject)
    {
        global $DB, $USER;
        $topicItems = array_unique(array_column($ojtTopics, 'ojt_task'));
        $topicItemsArray = [];
        foreach ($topicItems as $topicItem) {
            $topicItemObject = $DB->get_record('ojt_topic_item', array('topicid' => $ojtTopicObject->id, 'name' => $topicItem));
            if ($topicItemObject) {
                $topicItemsArray[$topicItem] = $topicItemObject->id;
            }
        }
        return $topicItemsArray;
    }

    public static function update_topicitem_completion($ojtTopics, $usersarray, $ojt, $ojtTopicObject, $topicItemsArray)
    {

        global $DB, $USER;

        foreach ($ojtTopics as $ojtTopic) {
            $userid = '';
            $ojtid = $ojt->id;
            $topicid = $ojtTopicObject->id;
            $topicitemid = $topicItemsArray[$ojtTopic['ojt_task']];
            if (isset($usersarray[$ojtTopic['username']])) {
                $userid = $usersarray[$ojtTopic['username']];
            }

            if ($userid && $ojtid && $topicid && $topicitemid) {
                $timemodified = time();
                if (isset($ojtTopic['completiondate']) && $ojtTopic['completiondate']) {
                    $unixdatetime = DateTime::createFromFormat('d/m/Y', $ojtTopic['completiondate'])->getTimestamp();
                    $timemodified = $unixdatetime;
                }

                $params = array(
                    'userid' => $userid,
                    'ojtid' => $ojtid,
                    'topicid' => $topicid,
                    'topicitemid' => $topicitemid,
                    'timemodified' => $timemodified,
                    'type' => OJT_CTYPE_TOPICITEM
                );
                $completionStatus = OJT_INCOMPLETE;
                if (isset($ojtTopic['completion']) && $ojtTopic['completion']) {
                    $completionStatus = OJT_COMPLETE;
                }

                if ($completion = $DB->get_record('ojt_completion', $params)) {
                    $completion->status = $completionStatus;
                    $completion->modifiedby = $USER->id;
                    $DB->update_record('ojt_completion', $completion);
                } else {
                    $completion = (object)$params;
                    $completion->status = $completionStatus;
                    $completion->modifiedby = $USER->id;
                    $completion->id = $DB->insert_record('ojt_completion', $completion);
                }
                ojt_update_topic_completion($userid, $ojtid, $topicid);
            }
        }
    }

    public static function update_ojt_topic($course, $csv)
    {
        global $DB, $USER;
        $usersarray = self::get_user_ids_and_enrol($course, $csv);
        $temptopics = array_unique(array_column($csv, 'ojt_topic'));
        $topics = array_intersect_key($csv, $temptopics);
        foreach ($topics as $csvTopicRow) {
            $topicName = $csvTopicRow['ojt_topic'];
            $ojt = $DB->get_record('ojt', array('course' => $course->id, 'name' => $topicName));
            if (!empty($ojt->id)) {
                $ojtTopics = array_filter($csv, function ($i) use ($topicName) {
                    return $i['ojt_topic'] == $topicName;
                });

                $ojtTopicObject = $DB->get_record('ojt_topic', array('ojtid' => $ojt->id, 'name' => $topicName));
                if (!empty($ojtTopicObject->id)) {

                    $topicItemsArray = self::get_topic_items($ojtTopics, $ojtTopicObject);
                    self::update_topicitem_completion($ojtTopics, $usersarray, $ojt, $ojtTopicObject, $topicItemsArray);
                }
            }
        }
    }
}
