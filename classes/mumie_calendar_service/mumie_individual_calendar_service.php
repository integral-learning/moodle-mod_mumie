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
 * This file describes a class used to manage individual calendar entries for MUMIE Tasks.
 *
 * Individual calendar entries represent a due date extension's deadline.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/mumie/classes/mumie_duedate_extension.php');
require_once($CFG->dirroot.'/mod/mumie/classes/mumie_calendar_service/mumie_calendar_service.php');

/**
 * Class manages individual calendar entries for MUMIE Tasks.
 *
 * Individual calendar entries represent a due date extension's deadline.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumie_individual_calendar_service extends mumie_calendar_service {
    /**
     * The user we want to manage the calendar for.
     *
     * If not specified, changes are made for all users without due date extensions.
     *
     * @var int
     */
    private $userid;

    /**
     * The extension we want to update the calendar for.
     *
     * @var mumie_duedate_extension
     */
    private $extension;

    /**
     * Calendar event type.
     *
     * @var string
     */
    const EVENT_TYPE = "duedate_extension";

    /**
     * Constructor
     *
     * @param  mixed $mumie Either mumie instance or id of mumie instance
     * @param  int $userid If id is set, the calendar is updated for the given user only.
     * @return void
     */
    public function __construct($mumie, $userid = 0) {
        parent::__construct($mumie);
        $this->title = get_string(
            "mumie_calendar_duedate_extension",
            "mod_mumie",
            $this->mumie->name
        );
        $this->userid = $userid;
        $this->event = $this->get_calendar_event(
            self::EVENT_TYPE,
            $this->userid
        );
        $this->extension = new mumie_duedate_extension($this->userid, $this->mumie->id);
        $this->extension->load();
    }

    /**
     * Create, update or delete individual calendar entries for MUMIE Task.
     *
     * Individual calendar entries are only shown to a single user who has a due date extension.
     *
     * @return void
     */
    public function update() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/mumie/locallib.php');
        require_once($CFG->dirroot.'/mod/mumie/classes/mumie_duedate_extension.php');

        if (!$this->extension->get_duedate() && $this->event) {
            $this->event->delete();
        } else if ($this->event) {
            $update = new \stdClass();
            $update->timestart = $this->extension->get_duedate();
            $this->event->update($update, false);
        } else {
            $this->create_calendar_event(
                self::EVENT_TYPE,
                $this->extension->get_duedate(),
                $this->userid
            );
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
        global $DB;
        // We cannot use $event->userid as it is bugged and not the value saved in db.
        $eventuser = $DB->get_record("event", ["id" => $event->id])->userid;
        return $event->userid == $eventuser;
    }
}
