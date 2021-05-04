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
 * English strings for ojt
 */

defined('MOODLE_INTERNAL') || die();

$string['accessdenied'] = 'Access denied';
$string['additem'] = 'Add topic item';
$string['addtopic'] = 'Add topic';
$string['allowcomments'] = 'Allow comments';
$string['allowfileuploads'] = 'Allow \'evaluator\' file uploads';
$string['allowselffileuploads'] = 'Allow \'owner\' file uploads';
$string['edititem'] = 'Edit topic item';
$string['evaluate'] = 'Evaluate';
$string['ojt:addinstance'] = 'Add instance';
$string['ojt:evaluate'] = 'Evaluate';
$string['ojt:evaluateself'] = 'Evaluate self';
$string['ojt:manage'] = 'Manage';
$string['ojt:view'] = 'View';
$string['ojt:signoff'] = 'Sign-off';
$string['ojt:witnessitem'] = 'Witness topic item completion';
$string['ojtfieldset'] = 'Custom example fieldset';
$string['ojtname'] = 'OJT name';
$string['ojtname_help'] = 'The title of your OJT activity.';
$string['ojt'] = 'OJT';
$string['ojtxforx'] = '{$a->ojt} - {$a->user}';
$string['competencies'] = 'Competencies';
$string['competencies_help'] = 'Here you can select which of the assigned course competencies should be marked as proficient upon completion of this topic.

Multiple competencies can be selected by holding down \<CTRL\> and and selecting the items.';
$string['completionstatus'] = 'Completion status';
$string['completionstatus0'] = 'Incomplete';
$string['completionstatus1'] = 'Required complete';
$string['completionstatus2'] = 'Complete';
$string['completiontopics'] = 'All required topics are complete and, if enabled, witnessed.';
$string['confirmtopicdelete'] = 'Are you sure you want to delete this topic?';
$string['confirmitemdelete'] = 'Are you sure you want to delete this topic item?';
$string['deleteitem'] = 'Delete topic item';
$string['deletetopic'] = 'Delete topic';
$string['edittopic'] = 'Edit topic';
$string['edittopics'] = 'Edit topics';
$string['error:ojtnotfound'] = 'OJT not found';
$string['evaluatestudents'] = 'Evaluate students';
$string['filesupdated']  = 'Files updated';
$string['itemdeleted'] = 'Topic item deleted';
$string['itemwitness'] = 'Item completion witness';
$string['manage'] = 'Manage';
$string['managersignoff'] = 'Manager sign-off';
$string['managertasktcompletionsubject'] = '{$a->user}  is awaiting your sign off for completion of topic {$a->topic} in {$a->courseshortname}';
$string['managertasktcompletionmsg'] = '{$a->user} has completed topic <a href="{$a->topicurl}">{$a->topic}</a>. This topic is now awaiting your sign-off.';
$string['modulename'] = 'OJT';
$string['modulenameplural'] = 'OJTs';
$string['modulename_help'] = 'The OJT module allows for student evaluation based on pre-configured OJT topics and items.';
$string['name'] = 'Name';
$string['notsignedoff'] = 'Not signed off';
$string['notopics'] = 'No topics';
$string['notwitnessed'] = 'Not witnessed';
$string['nousers'] = 'No users...';
$string['optional'] = 'Optional';
$string['optionalcompletion'] = 'Optional completion';
$string['pluginadministration'] = 'OJT administration';
$string['pluginname'] = 'OJT';
$string['printthisojt'] = 'Print this OJT';
$string['report'] = 'Report';
$string['signedoff'] = 'Signed off';
$string['topicdeleted'] = 'Topic deleted';
$string['topiccomments'] = 'Comments';
$string['topicitemfiles'] = 'Files';
$string['topicitemdeleted'] = 'Topic item deleted';
$string['type0'] = 'OJT';
$string['type1'] = 'Topic';
$string['type2'] = 'Item';
$string['updatefiles'] = 'Update files';
$string['witnessed'] = 'Witnessed';


$string['choosefile'] = 'CSV file to upload';
$string['completioninfo'] = 'Please tick the checkbox for completion record';
$string['topicimporttitle'] = 'Import ojt topics';

$string['topicimporthelp'] = 'Import ojt topic import help';

$string['importcsv'] = 'Import CSV';
$string['importcsv_help'] = 'Import CSV with correct headers';
$string['import'] = 'Import';
$string['importsuccess'] = 'Successfully imported';

$string['usercompletiontitle'] = 'Import ojt task completions';
$string['importcompletion'] = 'Import ojt task completions';
$string['uploadtopics'] = 'This will import ojt topics and tasks items.

The CSV file should contain the following columns in the first line of the file:
{$a}

The ojt name and ojt task names are based on column ojt_topic which will be created as activity in first section of the course if the specified ojt name has not been created. All task are required to completed as default.

The ojt tasks items are based on the column ojt_task. All task are mandatory and can upload evaluator and owner files as default.
';
$string['uploadcompletions'] = 'This will import ojt task items completion.

The CSV file should contain the following columns in the first line of the file:
{$a}

The ojt_topic should be exactly same for ojt name and ojt task name. Be careful for spaces and typo error. ojt_task column is exact task item under that task name. The username column should be username or idnumber as in profile of the user. The task item completion is based on completion column which can be either 0 or 1. The task item is marked as complete if completion value is 1 and 0 as incomplete.
';