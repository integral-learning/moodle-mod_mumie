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
 * redirect the user to the SSO plugin auth_mumie to view MUMIE content
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/mumie/lib.php');
require_login();


global $DB, $CFG, $USER, $PAGE;

$id = optional_param('id', 0, PARAM_INT);

$cm = get_coursemodule_from_id('mumie', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$mumietask = $DB->get_record('mumie', array('id' => $cm->instance));
if (!isset($mumietask->privategradepool)) {
    throw new moodle_exception(
        'gradepool_decision_pending',
        'mod_mumie',
        '',
        '',
        get_string('mumie_tag_disabled_help', 'mod_mumie')
    );
}
$redirecturl = new moodle_url('/auth/mumie/launch.php', array('id' => $mumietask->id));
if ($mumietask->launchcontainer == MUMIE_LAUNCH_CONTAINER_WINDOW || mod_mumie\locallib::is_safari_browser()) {
    $isSafari = mod_mumie\locallib::is_safari_browser();
    $isWindow = $mumietask->launchcontainer == MUMIE_LAUNCH_CONTAINER_WINDOW;
    $u_agent = $_SERVER['HTTP_USER_AGENT'];

    echo "<script>console.log('isSafari: {$isSafari}');console.log('isWindow: {$isWindow}');console.log('useragent: {$u_agent}');</script>";
    //redirect($redirecturl);
} else {
    $PAGE->set_cm($cm, $course);
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_title($course->shortname . ': ' . $mumietask->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url(new moodle_url('/mod/mumie/view.php'), array('id' => $id));

    echo $OUTPUT->header();
    echo "<iframe
        id='mumie_frame'
        height = '600px' width = '100%'
        src = '{$redirecturl}'
        webkitallowfullscreen
        mozallowfullscreen
        allowfullscreen>
    </iframe>";
    echo $OUTPUT->footer();
}
