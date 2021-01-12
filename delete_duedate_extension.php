<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

require_login(0, false);

$id = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

$returnurl = new \moodle_url('/mod/mumie/view.php', array("id" => $cmid, "action" => "grading"));
require_capability('mod/mumie:grantextension', context_system::instance());
mod_mumie\mumie_duedate_extension::delete_by_id($id);
redirect($returnurl);
