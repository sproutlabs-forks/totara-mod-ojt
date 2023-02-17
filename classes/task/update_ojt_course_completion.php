<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_ojt\task;
use mod_ojt\event\course_completion_updated;

defined('MOODLE_INTERNAL') || die();
define('OJT_CTYPE_OJT', 0);
define('OJT_CTYPE_TOPIC', 1);
define('OJT_CTYPE_TOPICITEM', 2);

/**
 * OJT completion statuses
 */
define('OJT_INCOMPLETE', 0);
define('OJT_REQUIREDCOMPLETE', 1);
define('OJT_COMPLETE', 2);


class update_ojt_course_completion extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('update_ojt_course_completion', 'mod_ojt');
    }

  
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
global $DB;
 $completion_type = 'ojt';

        $ojt_courses = $DB->get_records('course_completion_criteria', array('module' =>$completion_type));
        foreach ($ojt_courses as $ojt_course){
            $course = $ojt_course->course;
            $course_compl_criterias = $DB->get_records('course_completion_criteria', array('course' =>$course));
            if($course_compl_criterias && count($course_compl_criterias)==1){
                $ojt = $DB->get_record('ojt',['course'=>$course]);
                if($ojt && isset($ojt->id) && $ojt->id>0){
                    $ojt_completion = $DB->get_record('ojt_completion',['ojtid'=>$ojt->id,'type'=>OJT_CTYPE_TOPICITEM,'status'=>OJT_COMPLETE]);
                    if($ojt_completion && isset($ojt_completion->userid) && $ojt_completion->userid >0){
                        $course_completion  = $DB->get_record('course_completions', ['course' =>$course,'userid'=>$ojt_completion->userid]);
                        if($course_completion && isset($course_completion->id) && $ojt_completion->timemodified!=$course_completion->timecompleted){
                            
                            $param = array();
                            $param->id = $course_completion->id;
                            $param->timecompleted = $ojt_completion->timemodified;
                            $DB->update_record('course_completions', $param);
                            $event = course_completion_updated::create(
                                array(
                                    'context' => \context_system::instance(),
                                    'userid' => 2,
                                    'relateduserid' => $ojt_completion->userid,
                                    'courseid' => $course
                                ));
                            $course_completion_backup = array();
                            $course_completion_backup->userid =$ojt_completion->userid;
                            $course_completion_backup->course =$course;
                            $course_completion_backup->timeenrolled =$course_completion->timeenrolled;
                            $course_completion_backup->timestarted =$course_completion->timestarted;
                            $course_completion_backup->timecompleted =$course_completion->timecompleted;
                            $course_completion_backup->reaggregate =$course_completion->reaggregate;
                            $DB->insert_record('ojt_course_completions', $course_completion_backup);
                            $event->trigger();

                        }
                        
                    }
                }
                
                
                
                
            }


//            if($ojt_course && count($course)==1){
//                $course = current($course);
//                $course  = $course->id;
//                print_r($course);
//            }   
           
            
        }
      


    }
}
