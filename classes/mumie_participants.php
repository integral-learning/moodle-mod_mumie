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
 * Table of students with information about due dates and submissions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

use core\context\course;
use core_table\local\filter\filterset;
use core_user\table\participants_search;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_grader.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

/**
 * Table of students with information about due dates and submissions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_participants extends \table_sql {
    /**
     * The instance of mumie activity the table is being created for.
     *
     * @var \stdClass
     */
    private $mumie;

    /**
     * Course module id.
     *
     * @var int
     */
    private $cmid;

    /**
     * The ID of the course.
     *
     * @var int
     */
    private $courseid;

    /**
     * The instance of the course the table is being created for.
     *
     * @var \stdClass
     */
    private $course;

    /**
     * The course context.
     *
     * @var course|false
     */
    private $context;

    /**
     * An array that holds the role assignments of users.
     *
     * @var array
     */
    private $allroleassignments;

    /**
     * Constructor
     *
     * @param  string $uniqueid a string identifying this table. Used as a key in session vars.
     * @param  \stdClass $mumie
     * @param  int $cmid
     * @return void
     */
    public function __construct($uniqueid, $mumie, $cmid) {
        parent::__construct($uniqueid);
        $this->mumie = $mumie;
        $this->cmid = $cmid;
    }

    /**
     * Generate the fullname column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_fullname($data) {
        global $OUTPUT;

        return $OUTPUT->user_picture($data, array('size' => 35, 'courseid' => $this->course->id, 'includefullname' => true));
    }

    /**
     * Generate the duedate column
     *
     * @param  \stdClass $data
     * @return string
     */
    public function col_duedate($data) {
        $extension = new mumie_duedate_extension($data->id, $this->mumie->id);
        $extension->load();
        if ($extension->get_id() && $extension->get_id() > 0) {
            return $data->duedate . $this->edit_duedate_button($extension) . $this->delete_duedate_button($extension);
        } else {
            return $this->add_duedate_button($extension);
        }
    }

    /**
     * Generate the submissions column
     *
     * @param  \stdClass $data
     * @return string
     */
    public function col_submissions($data) {
        $submissionurl = new \moodle_url(
            '/mod/mumie/view.php',
            array("action" => "submissions", "id" => $this->cmid, "userid" => $data->id)
        );
        return \html_writer::start_tag("a", array("class" => "mumie_icon_btn", "href" => $submissionurl))
        . \html_writer::tag("span", "", array("class" => "icon fa fa-list fa-fw"))
        . \html_writer::end_tag("a");
    }

    /**
     * Generate an edit button for a due date extension.
     *
     * @param  mumie_duedate_extension $extension
     * @return string
     */
    private function edit_duedate_button($extension) {
        $formdata = array(
            "mumie" => $extension->get_mumie(),
            "userid" => $extension->get_userid(),
            "id" => $extension->get_id(),
            "duedate" => $extension->get_duedate()
        );
        return \html_writer::start_tag("span", array("class" => "mumie_duedate_edit_btn mumie_icon_btn"))
        . \html_writer::tag("span", "", array("class" => "icon fa fa-cog fa-fw"))
        . \html_writer::tag("span", json_encode($formdata), array("style" => "display: none;"))
        . \html_writer::end_tag("span");
    }

    /**
     * Generate a delete button for a due date extension.
     *
     * @param  mumie_duedate_extension $extension
     * @return string
     */
    private function delete_duedate_button($extension) {
        $url = new \moodle_url(
            '/mod/mumie/delete_duedate_extension.php',
            array("cmid" => $this->cmid, "duedateid" => $extension->get_id())
        );
        return \html_writer::start_tag("a", array("href" => $url, "class" => "mumie_icon_btn"))
        . \html_writer::tag("span", "", array("class" => "icon fa fa-trash fa-fw"))
        . \html_writer::end_tag("a");
    }

    /**
     * Generate an add button for a due date extension.
     *
     * @param  mumie_duedate_extension $extension
     * @return string
     */
    private function add_duedate_button($extension) {
        $formdata = array(
            "mumie" => $extension->get_mumie(),
            "userid" => $extension->get_userid()
        );
        return \html_writer::start_tag("span", array("class" => "mumie_duedate_add_btn mumie_icon_btn"))
        . \html_writer::tag("span", "", array("class" => "icon fa fa-plus fa-fw"))
        . \html_writer::tag("span", json_encode($formdata), array("style" => "display: none;"))
        . \html_writer::end_tag("span");
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        list($twhere, $tparams) = $this->get_sql_where();
        $psearch = new participants_search($this->course, $this->context, $this->filterset);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }

        $this->use_pages = true;
        $rawdata = $psearch->get_participants($twhere, $tparams, $sort, $this->get_page_start(), $this->get_page_size());
        $total = $rawdata->current()->fullcount ?? 0;
        $this->pagesize($pagesize, $total);

        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $user->duedate = mumie_grader::get_duedate($this->mumie, $user->id);
            $this->rawdata[$user->id] = $user;
        }
        $rawdata->close();

        if ($this->rawdata) {
            $this->allroleassignments = get_users_roles($this->context, array_keys($this->rawdata),
                    true, 'c.contextlevel DESC, r.sortorder ASC');
        } else {
            $this->allroleassignments = [];
        }

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars(true);
        }
    }

    /**
     * Render the participants table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $headers[] = get_string('mumie_duedate_extension', 'mod_mumie');
        $columns[] = 'duedate';

        $headers[] = get_string("mumie_submissions", "mod_mumie");
        $columns[] = 'submissions';

        $this->define_columns($columns);
        $this->define_headers($headers);

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     */
    public function set_filterset(filterset $filterset): void {
        // Get the context.
        $this->courseid = $filterset->get_filter('courseid')->current();
        $this->course = get_course($this->courseid);
        $this->context = \context_course::instance($this->courseid, MUST_EXIST);

        // Process the filterset.
        parent::set_filterset($filterset);
    }

    /**
     * Guess the base url for the participants table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new \moodle_url('/mod/mumie/view.php', array('action' => "grading", 'id' => $this->cmid));
    }

    /**
     * Get a fragment that can be used in an ORDER BY clause in participants table.
     *
     * @return \SQL|string fragment that can be used in an ORDER BY clause.
     */
    public function get_sql_sort() {
        $rawsort = parent::get_sql_sort();
        $sortarray = str_getcsv($rawsort, ', ');
        return implode(
            ', ',
            array_filter(
                $sortarray,
                function ($searchparam) {
                    return !$this->is_unsortable_column_header($searchparam);
                }
            )
        );
    }

    /**
     * Check if a given string contains a property the participants list cannot be sorted by.
     *
     * @param string $searchparam the search term we are checking
     * @return bool
     */
    private function is_unsortable_column_header($searchparam) {
        return $searchparam === null || strpos($searchparam, 'duedate') !== false || strpos($searchparam, 'submissions') !== false;
    }
}
