<?php

require_once('../../config.php');

global $PAGE, $DB, $CFG;
require_login();

$id = optional_param('id', 0, PARAM_INT);
$userid = required_param("userid", PARAM_INT);
$mumieid = required_param("mumie", PARAM_INT);

require_once($CFG->dirroot . '/mod/mumie/forms/duedate_form.php');
$mform = new duedate_form();
$record = $DB->get_record("mumie_duedate", array("mumie" => $mumieid, "userid" => $userid));
$PAGE->set_url(new moodle_url('/mod/mumie/duedateextension.php', array('id' => $id, 'userid' => $userid, 'mumieid' => $mumieid)));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/mumie/view.php', array('id' => $mform->get_data()->id, 'action' => 'grading')));
    exit;
} else if ($fromform = $mform->get_data()) {
    if($record) {
        $fromform->id = $record->id;
        $DB->update_record("mumie_duedate", $fromform);
    } else {
        $DB->insert_record("mumie_duedate", $fromform);
    }
    redirect(new moodle_url('/mod/mumie/view.php', array('id' => $fromform->cmid, 'action' => 'grading')));
    exit;
} else {
    $cm = get_coursemodule_from_id('mumie', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    /*
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);

    $PAGE->set_title("TODO");
    $PAGE->set_cm($cm, $course);
    $PAGE->set_pagelayout('incourse');

    $PAGE->set_url(new moodle_url('/mod/mumie/duedateextension.php', array('id' => $id, 'userid' => $userid, 'mumieid' => $mumieid)));
    $PAGE->navbar->add("[TODO] Grading", new moodle_url('/mod/mumie/view.php', array('id' => $id, 'action' => 'grading')));
    */
    $data = new stdClass();
    $data->mumie = $mumieid;
    $data->userid = $userid;
    $data->cmid = $id;
    if($record) {
        $data->id = $record->id;
        $data->duedate = $record->duedate;
    }
    $mform->set_data($data);
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}


function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}