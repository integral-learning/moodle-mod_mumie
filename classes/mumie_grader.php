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

    public function view_submissions($userid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/gradesync.php');

        $user = \core_user::get_user($userid);
        //$grades = grade_sync::get_mumie_grades($mumie, $userid);
        $gradeA = new \stdClass();
        $gradeA->timecreated = 1582790400;
        $gradeA->rawgrade = 60;
        $gradeB = new \stdClass();
        $gradeB->timecreated = 1582760400;
        $gradeB->rawgrade = 20;
        $grades = array($gradeA, $gradeB);

        usort($grades, function($a, $b) {
            return $b->timecreated <=> $a->timecreated;
        });

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable auth_index mumie_server_list_container';
        $table->head = array(
            get_string("mumie_grade_percentage", "mod_mumie"),
            get_string("mumie_submission_date", "mod_mumie"),
            get_string("mumie_override_gradebook", "mod_mumie")
        );

        $output = "";
        $output .= \html_writer::tag("h2", get_string("mumie_submissions_by", "mod_mumie", fullname($user)));
        $output .= \html_writer::tag("p", get_string("mumie_submissions_info", "mod_mumie"), array("style" => "margin-top: 1.5em;"));
        foreach($grades as $grade) {
            $overrideurl = new \moodle_url(
                "/mod/mumie/view.php", 
                array(
                    "id" => $this->cmid,
                    "action" => "overridegrade",
                    "userid" => $userid,
                    "rawgrade" => $grade->rawgrade,
                    "gradetimestamp" => $grade->timecreated
                )
            );

            $overrideicon = \html_writer::start_tag("a", array("class" => "mumie_icon_button", "href" => $overrideurl))
            . \html_writer::tag("span", "", array("class" => "icon fa fa-exchange fa-fw"))
            . \html_writer::end_tag("a");

            $table->data[] = array(
                $grade->rawgrade,
                strftime(
                    get_string('strftimedaydatetime', 'langconfig'),
                    $grade->timecreated
                ),
                $overrideicon
            );
        }
        $output .= \html_writer::table($table);
        return $output;
    }

    public function is_grade_valid($rawgrade, $userid, $timestamp) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/gradesync.php');

        $grades = gradesync::get_mumie_grades($this->mumie, $userid);
        if(!$grades) {
            return false;
        }
        foreach ($grades as $grade) {
            if ($grade->rawgrade == $rawgrade && $timestamp == $grade->timecreated) {
                return true;
            }
        }
        return false;

    }
}