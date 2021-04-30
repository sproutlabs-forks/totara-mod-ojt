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

/**
 *
 * @author      Bikram Kawan <bikram@sproutlabs.com.au>
 * @copyright   (c) Sproutlabs Pty Ltd.
 * @license     GNU General Public License version 3
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/importtopics_form.php');


// Fetch the course id from query string
$course_id = required_param('id', PARAM_INT);

// No anonymous access for this page, and this will
// handle bogus course id values as well
require_login($course_id);
// $PAGE, $USER, $COURSE, and other globals now set
$data = new stdClass();
$data->course = $COURSE;
$data->context = $PAGE->context;
$data->user_id_field_options = mod_ojt_plugin::get_user_id_field_options();
$data->course = $COURSE;
$data->context = $PAGE->context;

// Set some options for the filepicker
$file_picker_options = array(
    'accepted_types' => array('.csv'),
    'maxbytes' => '10mb');

$formdata = null;
$mform = new import_topics_form($PAGE->url->out(), array('data' => $data, 'options' => $file_picker_options));


$user_context = context_user::instance($USER->id);

$course_url = new moodle_url("{$CFG->wwwroot}/course/view.php", array('id' => $COURSE->id));

if ($mform->is_cancelled()) {

    // POST request, but cancel button clicked, or formdata not
    // valid. Either event, clear out draft file area to remove
    // unused uploads, then send back to course view
    get_file_storage()->delete_area_files($user_context->id, 'user', 'draft', file_get_submitted_draft_itemid(mod_ojt_plugin::FORMID_FILES));
    redirect($course_url);

} elseif (!$mform->is_submitted() || null == ($formdata = $mform->get_data())) {

    // GET request, or POST request where data did not
    // pass validation, either case display the form
    $heading = get_string('topicimporttitle', mod_ojt_plugin::PLUGIN_NAME);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($heading);

    // Display the form with a filepicker
    echo $OUTPUT->container_start();
    $mform->display();
    echo $OUTPUT->container_end();

    echo $OUTPUT->footer();

} else {

    // POST request, submit button clicked and formdata
    // passed validation, first check session spoofing
    require_sesskey();

    // Collect the input
    $area_files = get_file_storage()->get_area_files($user_context->id, 'user', 'draft', $formdata->{mod_ojt_plugin::FORMID_FILES}, null, false);
    $result = mod_ojt_plugin::import_topic_file($COURSE, array_shift($area_files));

    // Clean up the file area
    get_file_storage()->delete_area_files($user_context->id, 'user', 'draft', $formdata->{mod_ojt_plugin::FORMID_FILES});

    echo $OUTPUT->header();
    echo $OUTPUT->heading_with_help(get_string('topicimporttitle', mod_ojt_plugin::PLUGIN_NAME), 'topicimporthelp', mod_ojt_plugin::PLUGIN_NAME);

    // Output the processing result
    echo $OUTPUT->box(nl2br($result));


    echo $OUTPUT->footer();

}
