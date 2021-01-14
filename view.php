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
$action = optional_param("action", "open", PARAM_ALPHANUM);

$cm = get_coursemodule_from_id('mumie', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$mumietask = $DB->get_record('mumie', array('id' => $cm->instance));
$context = context_module::instance($cm->id);
$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

if ($action == "grading") {
    require_once($CFG->dirroot . '/mod/mumie/classes/mumie_grader.php');

    $grader = new mod_mumie\mumie_grader($mumietask, context_course::instance(SITEID), $cm->id);

    $PAGE->set_title($course->shortname . ': ' . $mumietask->name);
    $PAGE->set_heading(get_string("mumie_grading_settings", "mod_mumie") . ': ' . $mumietask->name);
    $url = new moodle_url('/mod/mumie/view.php', array("id" => $id, "action" => "grading"));

    $PAGE->set_url($url);
    $PAGE->navbar->add(get_string("mumie_grading_settings", "mod_mumie"));
    $PAGE->requires->js_call_amd('mod_mumie/view', 'init', array(json_encode($context->id)));

    if ($mumietask->duedate > 0){
        $dateelem = html_writer::tag(
            'p',
            strftime(
                get_string('strftimedaydatetime', 'langconfig'),
                $mumietask->duedate
            ),
            array('style' => 'font-weight: bold; margin-top:10px;')
        );
        $notification = get_string(
            "mumie_general_duedate", 
            "mod_mumie", 
            $dateelem
        );
    } else {
        $notification = get_string("mumie_duedate_not_set", "mod_mumie");
    }
    \core\notification::info($notification);


    echo $OUTPUT->header();
    echo $grader->view_grading_table();
    echo $OUTPUT->footer();
} else if ($action == "open") {

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
        redirect($redirecturl);
    } else {
        $PAGE->set_cm($cm, $course);
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
} else if ($action == "submissions") {
    require_once($CFG->dirroot . '/mod/mumie/classes/mumie_grader.php');

    require_capability("mod/mumie:viewgrades", $context);

    $grader = new mod_mumie\mumie_grader($mumietask, context_course::instance(SITEID), $cm->id);

    $userid = required_param("userid", PARAM_INT);

    $PAGE->set_title($course->shortname . ': ' . $mumietask->name);
    $PAGE->set_heading(get_string("mumie_grading_settings", "mod_mumie") . ': ' . $mumietask->name);
    $PAGE->set_url(new \moodle_url(
        '/mod/mumie/view.php', 
        array("action" => "submissions", "id" => $id, "userid" => $userid)
    ));
    $PAGE->navbar->add(get_string("mumie_grading_settings", "mod_mumie"), new moodle_url("/mod/mumie/view.php", array("action" => "grading", "id" => $id)));
    $PAGE->navbar->add(get_string("mumie_submissions", "mod_mumie"));

    echo $OUTPUT->header();
    echo $grader->view_submissions($userid);
    echo $OUTPUT->footer();
} else if ($action == "overridegrade") {
    require_once($CFG->dirroot . '/mod/mumie/classes/mumie_grader.php');
    require_capability("mod/mumie:overridegrades", $context);

    $userid = required_param("userid", PARAM_INT);
    //TODO: CHeck if grade is valid
    $grader = new mod_mumie\mumie_grader($mumietask, context_course::instance(SITEID), $cm->id);
    $grade = new stdClass();
    //$grade->timecreated = required_param("gradetimestamp", PARAM_INT);
    $grade->timecreated = time();
    $grade->rawgrade = required_param("rawgrade", PARAM_INT);
    $grade->overridden = gettype(time());
    $grade->userid = $userid;
    $grade->usermodified = $USER->id;
    
    if (!$grader->is_grade_valid($grade->rawgrade, $userid, $grade->timecreated)) {
        \core\notification::error("TODO: GRADE IS INVALID");
    }
    
    //TODO: Show success message
    mumie_override_grade($mumietask, $grade);
    \core\notification::success("TODO: CHANGED GRADE");
    redirect(new moodle_url("/mod/mumie/view.php", array("id" => $id, "action" => "grading")));
}
