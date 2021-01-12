<?php
namespace mod_mumie;

use core_user\table\participants_search;
use core_table\local\filter\filterset;



global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_grader.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');



class mumie_participants extends \table_sql {

    private $mumie;
    private $cmid;

    /**
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars.
     */
    function __construct($uniqueid, $mumie, $cmid) {
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

    public function col_test($data) {
        return $data->test;
    }

    public function col_duedate($data) {
        //return json_encode($data);
        //$formurl = new \moodle_url('/mod/mumie/duedateextension.php', array('id' => $this->cmid, 'mumie' => $this->mumie->id, 'userid' => $data->id));
        //$formurlnew moodle_url('/mod/mumie/view.php'), array('id' => $id, 'userid' => $data->userid, 'action' => 'show_extension_form')
        //return $formurl;

        $extension = new mumie_duedate_extension($data->id, $this->mumie->id);
        $extension->load();
        if($extension->get_id() && $extension->get_id() > 0) {
            return $data->duedate . $this->edit_duedate_button($extension) . $this->delete_duedate_button($extension);
        } else {
            return $this->add_duedate_button($extension);
        }


        return $data->duedate . "<span style='margin: 10px' class = 'mumie_duedate_extension_btn' title='" . "[TODO] title" . "'>"
        . '<span class="icon fa fa-cog fa-fw " title ="delete" aria-hidden="true" aria-label=""></span>'
        . "</a>";;
    }

    private function edit_duedate_button($extension) {
        $formdata = array(
            "mumie" => $extension->get_mumie(),
            "userid" => $extension->get_userid(),
            "id" => $extension->get_id()
        );
        return 
        "<span class='mumie_duedate_edit_btn mumie_icon_btn'>"
        . "<span class='icon fa fa-cog fa-fw'></span>"
        . "<span style='display:none'>"
        . json_encode($formdata)
        . "</span>"
        . "</span>";
    }

    private function delete_duedate_button($extension) {
        $url = new \moodle_url('/mod/mumie/delete_duedate_extension.php', array("cmid" => $this->cmid, "id" => $extension->get_id()));
        return "<a href=" . $url . " class='mumie_icon_btn'>"
        . "<span class='icon fa fa-trash fa-fw'></span>"
        . "</a>";
    }

    private function add_duedate_button($extension) {
        $formdata = array(
            "mumie" => $extension->get_mumie(),
            "userid" => $extension->get_userid()
        );
        return 
        "<span class='mumie_duedate_add_btn mumie_icon_btn'>"
        . "<span class='icon fa fa-plus fa-fw'></span>"
        . "<span style='display:none'>"
        . json_encode($formdata)
        . "</span>"
        . "</span>";
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

        $total = $psearch->get_total_participants_count($twhere, $tparams);

        $this->pagesize($pagesize, $total);

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = 'ORDER BY ' . $sort;
        }

        $rawdata = $psearch->get_participants($twhere, $tparams, $sort, $this->get_page_start(), $this->get_page_size());

        $this->rawdata = [];
        foreach ($rawdata as $user) {
            $user->test = "abcd";
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
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton='') {
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $headers[] = "test";
        $columns[] = 'test';

        $headers[] = '[TODO] duedate extension';
        $columns[] = 'duedate';

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
        $this->baseurl = new \moodle_url('/mod/mumie/grading.php', array('mumieid' => $this->mumie->id, 'id' => $this->cmid));
    }

}