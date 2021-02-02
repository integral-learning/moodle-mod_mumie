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
 * This file describes a class used to manage calendar entries for MUMIE Tasks.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

define("MUMIE_CALENDAR_EVENT_DUEDATE", "duedate");
define("MUMIE_CALENDAR_EVENT_DUEDATE_EXTENSION", "duedate_extension");

/**
 * This class is used to create, update and delete calendar entries for MUMIE Tasks.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_calendar_service {
    /**
     * The user we want to manage the calendar for.
     *
     * If not specified, changes are made for all users without due date extensions.
     *
     * @var int
     */
    private $userid;

    /**
     * The MUMIE Task we want to manage calendar entries for.
     *
     * @var \stdClass
     */
    private $mumie;

    /**
     * The due date event in the database.
     *
     * @var mixed
     */
    private $event;

    /**
     * Constructor
     *
     * @param  mixed $mumie Either mumie instance or id of mumie instance
     * @param  int $userid If id is set, the calendar is updated for the given user only.
     * @return void
     */
    public function __construct($mumie, $userid = 0) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');

        if (!is_object($mumie)) {
            $mumie = locallib::get_mumie_task($mumie);
        }

        $this->mumie = $mumie;
        $this->userid = $userid;
        $this->event = $this->get_calendar_event();
    }

    /**
     * Update calendar entries according for given $mumie and $userid
     *
     * @return void
     */
    public function update() {
        if ($this->userid) {
            $this->update_individual_calendar();
        } else {
            $this->update_general_calendar();
        }
    }

    /**
     * Create, update or delete general calendar entries for MUMIE Task.
     *
     * Only executed, if no userid is specified.
     * General calendar entries are shown to all users without due date extensions.
     *
     * @return void
     */
    private function update_general_calendar() {
        $hasduedate = isset($this->mumie->duedate) && $this->mumie->duedate > 0;
        if (!$this->event && $hasduedate) {
            $this->create_calendar_event();
        } else if ($this->event && $hasduedate) {
            $update  = new \stdClass();
            $update->name = get_string("mumie_calendar_duedate_name", "mod_mumie", $this->mumie->name);
            $update->timestart = $this->mumie->duedate;
            $this->event->update($update, false);
        } else if ($this->event && !$hasduedate) {
            $this->event->delete();
        }
    }

    /**
     * Create, update or delete individual calendar entries for MUMIE Task.
     *
     * Only executed, if userid is set.
     * Individual calendar entries are only show to a single user who has a due date extension.
     *
     * @return void
     */
    private function update_individual_calendar() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');
        require_once($CFG->dirroot.'/mod/mumie/classes/mumie_duedate_extension.php');

        $duedate = locallib::get_effective_duedate($this->userid, $this->mumie);
        $extension = new mumie_duedate_extension($this->userid, $this->mumie->id);
        $extension->load();
        if (!$extension->get_duedate() && $this->event) {
            $this->event->delete();
        } else if ($this->event) {
            $update = new \stdClass();
            $update->timestart = $duedate;
            $this->event->update($update, false);
        } else {
            $this->create_calendar_event();
        }
    }

    /**
     * Create a new calendar event.
     *
     * Can create both general and individual calendar entries.
     *
     * @return void
     */
    private function create_calendar_event() {
        global $CFG;
        require_once($CFG->dirroot.'/calendar/lib.php');
        require_once($CFG->dirroot.'/mod/mumie/classes/mumie_duedate_extension.php');

        if ($this->userid) {
            $eventtype = MUMIE_CALENDAR_EVENT_DUEDATE_EXTENSION;
            $name = get_string(
                "mumie_calendar_duedate_extension",
                "mod_mumie",
                $this->mumie->name
            );
            $extension = new mumie_duedate_extension($this->userid, $this->mumie->id);
            $extension->load();
            $timestart = $extension->get_duedate();
        } else {
            $eventtype = MUMIE_CALENDAR_EVENT_DUEDATE;
            $name = get_string("mumie_calendar_duedate_name", "mod_mumie", $this->mumie->name);
            $timestart = $this->mumie->duedate;
        }

        $event = new \stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->name = $name;
        $event->description = get_string("mumie_calendar_duedate_desc", "mod_mumie");
        $event->format = FORMAT_HTML;
        $event->courseid = $this->mumie->course;
        $event->userid = $this->userid;
        $event->modulename = "mumie";
        $event->instance = $this->mumie->id;
        $event->timestart = $timestart;
        $event->component = "mod_mumie";
        $event->visible = instance_is_visible("mumie", $this->mumie);

        \calendar_event::create($event, false);
    }

    /**
     * Loads calendar event from the database.
     *
     * @return \stdClass the event
     */
    private function get_calendar_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $params = array(
            "modulename" => "mumie",
            "instance" => $this->mumie->id
        );
        if ($this->userid) {
            $params["userid"] = $this->userid;
            $params["eventtype"] = MUMIE_CALENDAR_EVENT_DUEDATE_EXTENSION;
        } else {
            $params["eventtype"] = MUMIE_CALENDAR_EVENT_DUEDATE;
        }
        $oldeventdata = $DB->get_record(
            "event",
            $params
        );

        if ($oldeventdata) {
            return \calendar_event::load($oldeventdata->id);
        }

        return null;
    }

    /**
     * Delete all general and individual calendar entries for a given MUMIE Task.
     *
     * @param  \stdClass $mumie
     * @return void
     */
    public static function delete_all_calendar_events($mumie) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');
        $records = $DB->get_records(
            "event",
            array(
                "modulename" => "mumie",
                "instance" => $mumie->id
            )
        );

        foreach ($records as $record) {
            $event = \calendar_event::load($record->id);
            $event->delete();
        }
    }

    /**
     * Is event visible for given user?
     *
     * @param  \calendar_event $event
     * @param  int $userid
     * @return boolean
     */
    public static function is_event_visible($event, $userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');

        // We cannot use $event->userid as it is bugged and not the value saved in db.
        $eventuser = $DB->get_record("event", array("id" => $event->id))->userid;
        if ($event->eventtype == MUMIE_CALENDAR_EVENT_DUEDATE) {
            $effectiveduedate = locallib::get_effective_duedate(
                $userid,
                locallib::get_mumie_task($event->instance)
            );
            return $effectiveduedate == $event->timestart;
        }
        return $event->userid == $eventuser;
    }
}