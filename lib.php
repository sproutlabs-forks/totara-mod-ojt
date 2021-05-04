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

/**
 * Library of interface functions and constants for module ojt
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the ojt specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * OJT completion types
 */
define('OJT_CTYPE_OJT', 0);
define('OJT_CTYPE_TOPIC', 1);
define('OJT_CTYPE_TOPICITEM', 2);

/**
 * OJT completion statuses
 */
define('OJT_INCOMPLETE', 0);
define('OJT_REQUIREDCOMPLETE', 1);
define('OJT_COMPLETE', 2);

/**
 * OJT completion requirements
 */
define('OJT_REQUIRED', 0);
define('OJT_OPTIONAL', 1);

require_once("{$CFG->dirroot}/lib/navigationlib.php");
require_once("{$CFG->dirroot}/lib/enrollib.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/ojt/locallib.php');
/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function ojt_supports($feature)
{

    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the ojt into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $ojt Submitted data from the form in mod_form.php
 * @param mod_ojt_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted ojt record
 */
function ojt_add_instance(stdClass $ojt, mod_ojt_mod_form $mform = null)
{
    global $DB;

    $ojt->timecreated = time();

    // You may have to add extra stuff in here.

    $ojt->id = $DB->insert_record('ojt', $ojt);

    return $ojt->id;
}

/**
 * Updates an instance of the ojt in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $ojt An object from the form in mod_form.php
 * @param mod_ojt_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function ojt_update_instance(stdClass $ojt, mod_ojt_mod_form $mform = null)
{
    global $DB;

    $ojt->timemodified = time();
    $ojt->id = $ojt->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('ojt', $ojt);

    return $result;
}

/**
 * Removes an instance of the ojt from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function ojt_delete_instance($id)
{
    global $DB;

    if (!$ojt = $DB->get_record('ojt', array('id' => $id))) {
        return false;
    }

    $transaction = $DB->start_delegated_transaction();

    // Delete witnesses
    $DB->delete_records_select('ojt_item_witness', 'topicitemid IN (SELECT ti.id FROM {ojt_topic_item} ti JOIN {ojt_topic} t ON ti.topicid = t.id WHERE t.ojtid = ?)', array($ojt->id));

    // Delete signoffs
    $DB->delete_records_select('ojt_topic_signoff', 'topicid IN (SELECT id FROM {ojt_topic} WHERE ojtid = ?)', array($ojt->id));

    // Delete completions
    $DB->delete_records('ojt_completion', array('ojtid' => $ojt->id));

    // Delete comments
    $topics = $DB->get_records('ojt_topic', array('ojtid' => $ojt->id));
    foreach ($topics as $topic) {
        $DB->delete_records('comments', array('commentarea' => 'ojt_topic_item_' . $topic->id));
    }

    // Delete topic items
    $DB->delete_records_select('ojt_topic_item', 'topicid IN (SELECT id FROM {ojt_topic} WHERE ojtid = ?)', array($ojt->id));

    // Delete topics
    $DB->delete_records('ojt_topic', array('ojtid' => $ojt->id));

    // Finally, delete the ojt ;)
    $DB->delete_records('ojt', array('id' => $ojt->id));

    $transaction->allow_commit();

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $ojt The ojt instance record
 * @return stdClass|null
 */
function ojt_user_outline($course, $user, $mod, $ojt)
{

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $ojt the module instance record
 */
function ojt_user_complete($course, $user, $mod, $ojt)
{
}

/**
 * Obtains the specific requirements for completion.
 *
 * @param object $cm Course-module
 * @return array Requirements for completion
 */
function ojt_get_completion_requirements($cm)
{
    global $DB;

    $ojt = $DB->get_record('ojt', array('id' => $cm->instance));

    $result = array();

    if ($ojt->completiontopics) {
        $result[] = get_string('completiontopics', 'ojt');
    }

    return $result;
}

/**
 * Obtains the completion progress.
 *
 * @param object $cm Course-module
 * @param int $userid User ID
 * @return string The current status of completion for the user
 */
function ojt_get_completion_progress($cm, $userid)
{
    global $DB;

    // Get ojt details.
    $ojt = $DB->get_record('ojt', array('id' => $cm->instance), '*', MUST_EXIST);

    $result = array();

    if ($ojt->completiontopics) {
        $ojtcomplete = $DB->record_exists_select('ojt_completion',
            'ojtid = ? AND userid =? AND type = ? AND status IN (?, ?)',
            array($ojt->id, $userid, OJT_CTYPE_OJT, OJT_COMPLETE, OJT_REQUIREDCOMPLETE));
        if ($ojtcomplete) {
            $result[] = get_string('completiontopics', 'ojt');
        }
    }

    return $result;
}


/**
 * Obtains the automatic completion state for this ojt activity based on any conditions
 * in ojt settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function ojt_get_completion_state($course, $cm, $userid, $type)
{
    global $DB;

    // Get ojt.
    $ojt = $DB->get_record('ojt', array('id' => $cm->instance), '*', MUST_EXIST);

    // This means that if only view is required we don't end up with a false state.
    if (empty($ojt->completiontopics)) {
        return $type;
    }

    return $DB->record_exists_select('ojt_completion',
        'ojtid = ? AND userid =? AND type = ? AND status IN (?, ?)',
        array($ojt->id, $userid, OJT_CTYPE_OJT, OJT_COMPLETE, OJT_REQUIREDCOMPLETE));

}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link ojt_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function ojt_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0)
{
}

/**
 * Prints single activity item prepared by {@link ojt_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function ojt_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames)
{
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * @return boolean
 */
function ojt_cron()
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/totara/message/messagelib.php');

    $lastcron = $DB->get_field('modules', 'lastcron', array('name' => 'ojt'));

    // Send topic completion task to managers
    // Get all topic completions that happended after last cron run.
    // We can safely use the timemodified field here, as topics don't have comments ;)
    $sql = "SELECT bc.id AS completionid, u.id AS userid, u.*,
        b.id AS ojtid, b.name AS ojtname,
        t.id AS topicid, t.name AS topicname,
        c.shortname AS courseshortname
        FROM {ojt_completion} bc
        JOIN {ojt} b ON bc.ojtid = b.id
        JOIN {course} c ON b.course = c.id
        JOIN {ojt_topic} t ON bc.topicid = t.id
        JOIN {user} u ON bc.userid = u.id
        WHERE bc.type = ? AND bc.status = ? AND bc.timemodified > ?
        AND b.id IN (SELECT id FROM {ojt} WHERE managersignoff = 1)";
    $tcompletions = $DB->get_records_sql($sql, array(OJT_CTYPE_TOPIC, OJT_COMPLETE, $lastcron));
    foreach ($tcompletions as $completion) {
        $managerids = \totara_job\job_assignment::get_all_manager_userids($completion->userid);
        foreach ($managerids as $managerid) {
            $manager = core_user::get_user($managerid);
            $eventdata = new stdClass();
            $eventdata->userto = $manager;
            $eventdata->userfrom = $completion;
            $eventdata->icon = 'elearning-complete';
            $eventdata->contexturl = new moodle_url('/mod/ojt/evaluate.php',
                array('userid' => $completion->userid, 'bid' => $completion->ojtid));
            $eventdata->contexturl = $eventdata->contexturl->out();
            $strobj = new stdClass();
            $strobj->user = fullname($completion);
            $strobj->ojt = format_string($completion->ojtname);
            $strobj->topic = format_string($completion->topicname);
            $strobj->topicurl = $eventdata->contexturl;
            $strobj->courseshortname = format_string($completion->courseshortname);
            $eventdata->subject = get_string('managertasktcompletionsubject', 'ojt', $strobj);
            $eventdata->fullmessage = get_string('managertasktcompletionmsg', 'ojt', $strobj);
            // $eventdata->sendemail = TOTARA_MSG_EMAIL_NO;

            tm_task_send($eventdata);
        }
    }

    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function ojt_get_extra_capabilities()
{
    return array(
        'mod/ojt:evaluate',
        'mod/ojt:signoff',
        'mod/ojt:manage'
    );
}


/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function ojt_get_file_areas($course, $cm, $context)
{
    return array();
}

/**
 * File browsing support for ojt file areas
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 * @package mod_ojt
 * @category files
 *
 */
function ojt_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename)
{
    return null;
}

/**
 * Serves the files from the ojt file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the ojt's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @category files
 *
 * @package mod_ojt
 */
function ojt_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array())
{
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    $userid = $args[0];
    require_once($CFG->dirroot . '/mod/ojt/locallib.php');
    if (!(ojt_can_evaluate($userid, $context) || $userid == $USER->id)) {
        // Only evaluators and/or owners have access to files
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_ojt/$filearea/$relativepath";
    if ((!$file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        send_file_not_found();
    }

    // finally send the file
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding ojt nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the ojt module instance
 * @param stdClass $course current course record
 * @param stdClass $module current ojt instance record
 * @param cm_info $cm course module information
 */
function ojt_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm)
{
    $context = context_module::instance($cm->id);
    if (has_capability('mod/ojt:evaluate', $context) || has_capability('mod/ojt:signoff', $context)) {
        $link = new moodle_url('/mod/ojt/report.php', array('cmid' => $cm->id));
        $node = $navref->add(get_string('evaluatestudents', 'ojt'), $link, navigation_node::TYPE_SETTING);
    }

}

/**
 * Extends the settings navigation with the ojt settings
 *
 * This function is called when the context for the page is a ojt module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $ojtnode ojt administration node
 */
function ojt_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $ojtnode = null)
{
    global $PAGE;

    if (has_capability('mod/ojt:evaluate', $PAGE->cm->context) || has_capability('mod/ojt:signoff', $PAGE->cm->context)) {
        $link = new moodle_url('/mod/ojt/report.php', array('cmid' => $PAGE->cm->id));
        $node = navigation_node::create(get_string('evaluatestudents', 'ojt'),
            new moodle_url('/mod/ojt/report.php', array('cmid' => $PAGE->cm->id)),
            navigation_node::TYPE_SETTING, null, 'mod_ojt_evaluate',
            new pix_icon('i/valid', ''));
        $ojtnode->add_node($node);
    }

    if (has_capability('mod/ojt:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('edittopics', 'ojt'),
            new moodle_url('/mod/ojt/manage.php', array('cmid' => $PAGE->cm->id)),
            navigation_node::TYPE_SETTING, null, 'mod_ojt_manage',
            new pix_icon('t/edit', ''));
        $ojtnode->add_node($node);
    }
}


/**
 * Comments helper functions and callbacks
 *
 */

/**
 * Validate comment parameters, before other comment actions are performed
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 * @package  block_comments
 * @category comment
 *
 */
function ojt_comment_validate($comment_param)
{
    if (!strstr($comment_param->commentarea, 'ojt_topic_item_')) {
        throw new comment_exception('invalidcommentarea');
    }
    if (empty($comment_param->itemid)) {
        throw new comment_exception('invalidcommentitemid');
    }

    return true;
}

/**
 * Running addtional permission check on plugins
 *
 * @param stdClass $args
 * @return array
 * @package  block_comments
 * @category comment
 *
 */
function ojt_comment_permissions($args)
{
    global $CFG;
    require_once($CFG->dirroot . '/mod/ojt/locallib.php');

    if (!ojt_can_evaluate($args->itemid, $args->context)) {
        return array('post' => false, 'view' => true);
    }

    return array('post' => true, 'view' => true);
}

function ojt_comment_template()
{
    global $OUTPUT, $PAGE;

    // Use the totara default comment template
    $renderer = $PAGE->get_renderer('totara_core');

    return $renderer->comment_template();
}

function ojt_extend_navigation_course($navigation, $course, $context)
{
    global $CFG, $PAGE;
    $courseid = $PAGE->course->id;

    $url = new moodle_url('/mod/ojt/importtopics.php', array('id' => $courseid));
    $navigation->add(get_string('topicimporttitle', 'ojt'), $url, navigation_node::TYPE_USER, null, null, new pix_icon('i/import', ''));

    $usercompletionurl = new moodle_url('/mod/ojt/importusercompletion.php', array('id' => $courseid));
    $navigation->add(get_string('usercompletiontitle', 'ojt'), $usercompletionurl, navigation_node::TYPE_USER, null, null, new pix_icon('i/import', ''));

}

class mod_ojt_plugin
{

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
            if(isset($usersarray[$ojtTopic['username']])){
                $userid = $usersarray[$ojtTopic['username']];  
            }

            if ($userid && $ojtid && $topicid && $topicitemid) {
                $timemodified = time();
                if (isset($ojtTopic['completiondate']) && $ojtTopic['completiondate']) {
                    $unixdatetime = DateTime::createFromFormat('d/m/Y', $ojtTopic['completiondate'])->getTimestamp();
                    $timemodified = $unixdatetime;
                }
                
                $params = array('userid' => $userid,
                    'ojtid' => $ojtid,
                    'topicid' => $topicid,
                    'topicitemid' => $topicitemid,
                    'timemodified' => $timemodified,
                    'type' => OJT_CTYPE_TOPICITEM);
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

