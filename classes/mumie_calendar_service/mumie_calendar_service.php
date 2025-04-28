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
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

/**
 * This class is used to create, update and delete general calendar entries for MUMIE Tasks.
 *
 * A general calendar entry represents a MUMIE Task's due date in the moodle calendar.
 *
 * @package mod_mumie
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_calendar_service {


    /**
     * The MUMIE Task we want to manage calendar entries for.
     *
     * @var \stdClass
     */
    protected $mumie;

    /**
     * The due date event in the database.
     *
     * @var \calendar_event
     */
    protected $event;

    /**
     * The title displayed in the calendar entry.
     *
     * @var string
     */
    protected $title;

    /**
     * Calendar event type.
     *
     * @var string
     */
    const EVENT_TYPE = "duedate";

    /**
     * Constructor
     *
     * @param  mixed $mumie Either mumie instance or id of mumie instance
     * @return void
     */
    public function __construct($mumie) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');

        if (!is_object($mumie)) {
            $mumie = locallib::get_mumie_task($mumie);
        }
        $this->mumie = $mumie;
        $this->event = $this->get_calendar_event(self::EVENT_TYPE);
        $this->title = get_string("mumie_calendar_duedate_name", "mod_mumie", $this->mumie->name);
    }

    /**
     * Create, update or delete general calendar entries for MUMIE Task.
     *
     * General calendar entries are shown to all users without due date extensions.
     *
     * @return void
     */
    public function update() {
        $hasduedate = isset($this->mumie->duedate) && $this->mumie->duedate > 0;
        if (!$this->event && $hasduedate) {
            $this->create_calendar_event(
                self::EVENT_TYPE,
                $this->mumie->duedate
            );
        } else if ($this->event && $hasduedate) {
            $update = new \stdClass();
            $update->name = $this->title;
            $update->timestart = $this->mumie->duedate;
            $this->event->update($update, false);
        } else if ($this->event && !$hasduedate) {
            $this->event->delete();
        }
    }

    /**
     * Create a new calendar event for all users without due date extensions.
     *
     * @param  string $eventtype
     * @param  int $timestart
     * @param  int $userid
     * @return void
     */
    protected function create_calendar_event($eventtype, $timestart, $userid = null) {
        global $CFG;
        require_once($CFG->dirroot.'/calendar/lib.php');
        $event = new \stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->name = $this->title;
        $event->description = get_string("mumie_calendar_duedate_desc", "mod_mumie");
        $event->format = FORMAT_HTML;
        $event->courseid = $this->mumie->course;
        if ($userid) {
            $event->userid = $userid;
        }
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
     * @param  string $type
     * @param  int $userid
     * @return \calendar_event
     */
    protected function get_calendar_event($type, $userid = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $params = [
            "modulename" => "mumie",
            "instance" => $this->mumie->id,
            "eventtype" => $type,
        ];

        if ($userid) {
            $params["userid"] = $userid;
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
            [
                "modulename" => "mumie",
                "instance" => $mumie->id,
            ]
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
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/locallib.php');
        require_once($CFG->dirroot . '/mod/mumie/lib.php');
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_calendar_service/mumie_individual_calendar_service.php');

        if ($event->eventtype == self::EVENT_TYPE) {
            $effectiveduedate = mumie_get_effective_duedate(
                $userid,
                locallib::get_mumie_task($event->instance)
            );
            return $effectiveduedate == $event->timestart;
        } else {
            return mumie_individual_calendar_service::is_event_visible($event, $userid);
        }
    }
}
