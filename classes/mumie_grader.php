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
 * This file describes a class used to dispay information about a MUMIE Tasks due date extensions and submissions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;
use core_user\table\participants_filterset;

/**
 * mumie_grader is used to display information about due date extensions and submissions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_grader {
    private $mumie;
    private $gradeitem;
    private $course;
    private $context;
    private $cmid;
    
    /**
     * __construct
     *
     * @param  \stdClass $mumie
     * @param  int $context
     * @param  int $cmid course module id.
     * @return void
     */
    public function __construct($mumie, $context, $cmid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/mod/mumie/locallib.php");
        $this->mumie = $mumie;
        $this->course = $DB->get_record('course', array('id' => $this->mumie->course), '*', MUST_EXIST);
        $this->context = $context;
        $this->cmid = $cmid;
    }
    
    /**
     * Get a table of students with due date extensions and submissions as html string.
     *
     * @return string
     */
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
    
    /**
     * Get formatted duedate or placeholder for a student.
     *
     * @param  \stdClass $mumie
     * @param  int $userid
     * @return string
     */
    public static function get_duedate($mumie, $userid) {
        global $DB;
        $duedate = $DB->get_record("mumie_duedate", array('userid' => $userid, 'mumie' => $mumie->id));

        return $duedate ? strftime(
            get_string('strftimedaydatetime', 'langconfig'),
            $duedate->duedate
        ) : "-";
    }
    
    /**
     * View all submissions by a student as html table.
     *
     * @param  int $userid
     * @return string
     */
    public function view_submissions($userid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/gradesync.php');

        $user = \core_user::get_user($userid);
        $grades = gradesync::get_all_grades_for_user($this->mumie, $userid);
        
        $table = new \html_table();
        $table->head = array(
            get_string("mumie_grade_percentage", "mod_mumie"),
            get_string("mumie_submission_date", "mod_mumie"),
            get_string("mumie_override_gradebook", "mod_mumie")
        );
        
        $output = "";
        $output .= \html_writer::tag("h2", get_string("mumie_submissions_by", "mod_mumie", fullname($user)));
        $output .= \html_writer::tag(
            "p",
            get_string("mumie_submissions_info", "mod_mumie"),
            array("style" => "margin-top: 1.5em;")
        );
        
        if ($grades) {
            usort($grades, function($a, $b) {
                return $b->timecreated <=> $a->timecreated;
            });
            
            foreach ($grades as $grade) {
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
        } else {
            $table->data[] = array(\html_writer::tag("i", get_string("mumie_no_submissions", "mod_mumie")), "", "");
        }
        

        $output .= \html_writer::table($table);  
        return $output;
    }
    
    /**
     * Verify that the given parameters really belong to a MUMIE grade.
     *
     * @param  float $rawgrade
     * @param  int $userid
     * @param  int $timestamp
     * @return boolean
     */
    public function is_grade_valid($rawgrade, $userid, $timestamp) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/gradesync.php');

        $grades = gradesync::get_all_grades_for_user($this->mumie, $userid);
        if (!$grades) {
            return false;
        }
        foreach ($grades as $grade) {
            if (intval($grade->rawgrade) == intval($rawgrade) && $timestamp == $grade->timecreated) {
                return true;
            }
        }
        return false;
    }
}