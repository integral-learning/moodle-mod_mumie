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
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
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

// Used in settings.php.
$string['mumie_table_header_name'] = 'Server name';
$string['mumie_table_header_url'] = 'URL prefix';
$string['mumie_server_list_heading'] = 'Configured MUMIE servers';
$string['mumie_edit_button'] = 'Edit';
$string['mumie_delete_button'] = 'Delete';
$string['mumie_add_server_button'] = 'Add MUMIE Server';

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

// These strings are used in add activity form.
$string['mumie_form_activity_header'] = 'General';
$string['mumie_form_activity_name'] = 'Name';
$string['mumie_form_activity_server'] = 'MUMIE server';
$string['mumie_form_activity_server_help'] = 'Please select a MUMIE server to get an updated list of available tasks and courses.';
$string['mumie_form_activity_course'] = 'MUMIE course';
$string['mumie_form_activity_problem'] = "MUMIE problem";
$string['mumie_form_activity_container'] = 'Launch container';
$string['mumie_form_activity_container_help'] = 'Please select whether the activity should be openend in a new browser tab or embedded into moodle.';
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
