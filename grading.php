<?php
//defined('MOODLE_INTERNAL') || die;

require_once("../../config.php");
global $PAGE, $CFG;
$id = required_param('id', PARAM_ALPHANUM);

require($CFG->dirroot . "/mod/mumie/locallib.php");
$mumie = mod_mumie\locallib::get_mumie_task($id);
echo "henlo";
