<?php

namespace mod_mumie;
defined('MOODLE_INTERNAL') || die;

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_user\table\participants_filterset;


class mumie_grader {
    private $mumie;
    private $gradeitem;
    private $course;
    private $context;
    private $cmid;

    public function __construct($mumie, $context, $cmid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/mod/mumie/locallib.php");
        $this->mumie = $mumie;
        $this->course = $DB->get_record('course', array('id' => $this->mumie->course), '*', MUST_EXIST);
        $this->context = $context;
        $this->cmid = $cmid;
    }

    public function view_grading_table() {
        global $PAGE, $CFG;
        $output = "";
        
        $filterset = new participants_filterset();
        $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$this->course->id]));
        $filterset->add_filter(new integer_filter('roles', filter::JOINTYPE_DEFAULT, [5]));

        require_once($CFG->dirroot . "/mod/mumie/classes/mumie_participants.php");

        $participanttable = new mumie_participants("user-index-participants-{$this->course->id}", $this->mumie, $this->cmid);

        // Do this so we can get the total number of rows.
        ob_start();
        $participanttable->set_filterset($filterset);
        $participanttable->out(20, true);
        $participanttablehtml = ob_get_contents();
        ob_end_clean();

        $output .= $participanttablehtml;

        return $output;
    }

    public static function get_duedate($mumie, $userid) {
        global $DB;


        $duedate = $DB->get_record("mumie_duedate", array('userid' => $userid, 'mumie' => $mumie->id));

        return $duedate ? strftime(
            get_string('strftimedaydatetime', 'langconfig'),
            $duedate->duedate
        ) : "-";
    }
}