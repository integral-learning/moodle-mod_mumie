<?php

namespace mod_mumie;
defined('MOODLE_INTERNAL') || die;


class mumie_grader {
    private $mumie;
    private $gradeitem;
    public function __construct($mumieid) {
        require_once($CFG->dirroot . "/mod/mumie/locallib.php");
        $this->mumie = locallib::get_mumie_task($id);
    }

    public function view() {
        
    }
}