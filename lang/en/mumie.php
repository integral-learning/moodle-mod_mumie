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
 * Strings for mod_mumie
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'MUMIE Task';
$string['modulename'] = 'MUMIE Task';
$string['modulename_help'] = '<p>This activity module enables the usage of content from the MUMIE e-learning platform and automatic grade synchronization. A MUMIE tasks represents an individual graded problem from a MUMIE course.</p>
<p><strong>What is MUMIE?</strong></p>
<p>MUMIE is an e-learning platform for learning and teaching mathematics and computer science. It grew out of the needs of practical teaching at the interface between high-school and university. MUMIE is highly flexible and can be integrated with other learning and content management systems. Its courses and high quality course material is easily adjusted to any kind of pedagogical scenario. It has built in learning and training environments with wiki-like dedicated social networks for virtual tutorials and self-organized learning enhancing cognitive and meta-cognitive skills. Powerful authoring tools support the production of new content. This opens the door to new, challenging and more efficient pedagogical scenarios.<br /><br /> For more information, please visit our <a href="https://www.mumie.net/platform/" target="_blank" rel="nofollow noreferrer noopener">website</a>.</p>
<p><strong>Key features of MUMIE Task</strong></p>
<ul>
<li><strong>Use MUMIE Tasks in moodle</strong><br /> Add any number of MUMIE Tasks to your moodle course with an easy to use form.</li>
<li><strong>Get the latest content</strong><br /> As soon as new content is available on a MUMIE server, you can immediately use it in your moodle courses. No updates are required!</li>
<li><strong>Multiple languages</strong><br /> Most content on MUMIE servers is available in multiple languages. You can change the language in which a MUMIE task is displayed for all students with the click of a button.</li>
<li><strong>Automatic grade synchronization</strong><br /> All MUMIE tasks are graded and their results are automatically added to the moodle gradebook.</li>
<li><strong>Single sign on and automatic sign out</strong><br /> Students don\'t need to create a new account or login to MUMIE servers. It\'s done for them automatically as soon as they start a MUMIE task. To secure their data, they are also automatically logged out of all MUMIE servers, when they log out of moodle.</li>
</ul>';
$string['modulenameplural'] = 'MUMIE tasks';
$string['pluginadministration'] = 'MUMIE administration';
$string['mumieintro'] = 'Activity description';

// Used in index.php.
$string['nomumieinstance'] = 'There are no MUMIE instances in this course';
$string['name'] = 'Name';

// These strings are used in add activity form.
$string['mumie_form_activity_header'] = 'General';
$string['mumie_form_activity_name'] = 'Name';
$string['mumie_form_activity_server'] = 'MUMIE server';
$string['mumie_form_activity_server_help'] = 'Please select a MUMIE server to get an updated list of available tasks and courses.';
$string['mumie_form_activity_course'] = 'MUMIE course';
$string['mumie_form_activity_problem'] = "MUMIE problem";
$string['mumie_form_activity_problem_help'] = "A MUMIE problem is a single graded exercise on MUMIE";
$string['mumie_form_activity_container'] = 'Launch container';
$string['mumie_form_activity_container_help'] = 'Please select whether the activity should be openend in a new browser tab or embedded into moodle.<br><br>Please note that embedded MUMIE Tasks cannot be opened with Safari due to technical limitations. This MUMIE Task will be opened in a tab instead for Safari users';
$string['mumie_form_activity_container_embedded'] = 'embedded';
$string['mumie_form_activity_container_window'] = 'New window';
$string['mumie_form_activity_language'] = 'Language';
$string['mumie_form_activity_language_help'] = 'Please select the language in which the task should be displayed.';
$string['mumie_form_server_added'] = 'MUMIE server was added';
$string['mumie_form_add_server_button'] = 'Add MUMIE server';
$string['mumie_form_coursefile'] = 'Path to MUMIE course meta file';
$string['mumie_form_points'] = 'Maximum points';
$string['mumie_form_points_help'] = 'Please enter the maximal amout of points a student can get for completing the activity.<br>Grades are calculated and updated automatically.';
$string['mumie_form_missing_server'] = 'We could not find a configuration for the MUMIE server that is being used in this MUMIE Task.<br><br>
The properties <i>MUMIE server</i>, <i>MUMIE course</i>, <i>MUMIE problem</i> and <i>Language</i> are locked for editing until a new MUMIE server is added for the following URL prefix:<br><br>';
// This is taken and adapted from mod_quiz version 2018051400.
$string['completionpass'] = 'Require passing grade';
$string['completionpass_help'] = 'If enabled, this activity is considered completed when the student receives a passing grade, with the pass grade set in the gradebook.
<br><br><b>Please Note:</b><br>
Grades for MUMIE Tasks are only updated, when the gradebook is opened. If you want the current completion status of all students, remember to open the gradebook overview page first';
$string['gradetopassnotset'] = 'This MUMIE task does not have a <i>grade to pass</i> set so you cannot use this option. Please use the require grade setting instead.';
$string['gradetopassmustbeset'] = '<i>Grade to pass</i> cannot be zero as this activity has its completion method set to require passing grade. Please set a non-zero value.';
$string['mumie_form_due_date'] = 'Due date';
$string['mumie_form_due_date_help'] = 'If enabled, grades that were earned after the selected date will not be synchronized with Moodle';
$string['mumie_form_due_date_must_be_future'] = 'You must select a date in the future!';
$string['mumie_form_grade_pool'] = 'Share grades with other courses';
$string['mumie_form_grade_pool_help'] = 'Choose whether to share grades with other MOODLE courses.
<br>If sharing is enabled, points that were earned for MUMIE problems in other courses will be automatically synchronized with this course\'s gradebook.
<br>If not, this course will neither be able to import nor to export grades.';
$string['mumie_form_grade_pool_warning'] = '<b style="color:red">Warning:</b><br> This decision is <b>final</b> and affects all other MUMIE Tasks in this course';
$string['mumie_form_grade_pool_note'] = '<b>Note:</b><br> This decision was <b>final</b> and affects all other MUMIE Tasks in this course';
$string['mumie_form_filter'] = 'Filter MUMIE problems';
$string['mumie_form_complete_course'] = 'Link the entire course';
$string['mumie_form_complete_course_help'] = 'The user will only be logged in and redirected to the course overview page. Grades will <b>not</b> be synchronized for this MUMIE Task.';

// Used in mumieserver form.
$string['mumie_form_required'] = 'required';
$string['mumie_form_server_not_existing'] = 'There is no MUMIE server for this URL';
$string['mumie_form_already_existing_config'] = 'There is already a configuration for this URL prefix';
$string['mumie_form_already_existing_name'] = 'There is already a configuration for this name';
$string['mumie_form_title'] = 'Configure MUMIE Server';
$string['mumie_form_server_config'] = 'MUMIE server configuration';
$string['mumie_server_name'] = 'Server name';
$string['mumie_server_name_help'] = 'Please insert a unique name for this configuration.';
$string['mumie_form_server_btn_submit'] = 'Submit';
$string['mumie_form_server_btn_cancel'] = 'Cancel';
$string['mumie_url_prefix'] = 'MUMIE URL Prefix';
$string['mumie_url_prefix_help'] = 'Specify the MUMIE URL prefix  <br><br> e.g. <b>https://www.ombplus.de/ombplus</b> <br><br> There can only be a single configuration for any URL prefix.';

// Used in course view.
$string['mumie_due_date'] = 'Deadline';
$string['mumie_tag_disabled'] = 'Disabled';
$string['mumie_tag_disabled_help'] = 'This activity is disabled because the configuration is not completed. Please open the settings of this MUMIE Task.';

// Used for drag&drop functionality.
$string['parameter_missing'] = 'The uploaded file is incompatible!';
$string['dndupload_message'] = 'Add as a new MUMIE Task';
$string['server_config_missing'] = '<br><br>We could not find a configuration for the MUMIE server that is being used in this MUMIE Task. Please ask your administrator to add a MUMIE server configuration for the following URL prefix:<br><br><b>{$a}</b>';
$string['dnd_addmessage'] = 'Add MUMIE Task here';
$string['dnd_addmessage_multiple'] = 'Add MUMIE Tasks here';


// Capabilities.
$string['mumie:addinstance'] = 'Add a new MUMIE Task';
$string['mumie:viewgrades'] = 'View all grades for a course\'s MUMIE Task';
