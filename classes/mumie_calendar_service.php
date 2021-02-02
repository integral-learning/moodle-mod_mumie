<?php

namespace mod_mumie;

define("MUMIE_CALENDAR_EVENT_DUEDATE", "duedate");
define("MUMIE_CALENDAR_EVENT_DUEDATE_EXTENSION", "duedate_extension");

class mumie_calendar_service {
    private $userid;
    private $mumie;
    private $event;

    public function __construct($mumie, $userid = 0) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');
        
        if(!is_object($mumie)) {
            $mumie = locallib::get_mumie_task($mumie);
        }

        $this->mumie = $mumie;
        $this->userid = $userid;
        $this->event = $this->get_calendar_event();
    }

    public function update() {
        if ($this->userid) {
            $this->update_individual_calendar();
        } else {
            $this->update_general_calendar();
        }
    }

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

    private function update_individual_calendar() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');
        $duedate = locallib::get_effective_duedate($this->userid, $this->mumie);
        if(!$duedate) {
            $this->event->delete();
        } else if ($this->event) {
            $update = new \stdClass();
            $update->timestart = $duedate;
            $this->event->update($update, false);
        } else {
            $this->create_calendar_event();
        }

    }

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

        foreach($records as $record) {
            $event = \calendar_event::load($record->id);
            $event->delete();
        }
    }

    public static function is_event_visible($event, $userid) {
        global $DB;
        // We cannot use $event->userid as it is bugged and not the value saved in db.
        $eventuser = $DB->get_record("event", array("id" => $event->id))->userid;
        if ($event->eventtype == MUMIE_CALENDAR_EVENT_DUEDATE) {
            return true;
        }
        return $event->userid == $eventuser;
    }
}