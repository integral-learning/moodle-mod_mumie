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
 * Handle the course_module_viewed event.
 *
 * @package     mod_mumie
 * @copyright   2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author      Dung Pham (dung.pham@integral-learning.de)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

use dml_exception;
use mod_mumie\event\course_module_viewed;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mumie/classes/mumie_calendar_service/mumie_individual_calendar_service.php');

/**
 * Handle the course_module_viewed event.
 *
 * @package     mod_mumie
 * @copyright   2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author      Dung Pham (dung.pham@integral-learning.de)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle course_module_viewed event.
     *
     * Set individual duedate for a user if the MUMIE Task has a timelimit
     *
     * @param course_module_viewed $event
     * @return void
     * @throws dml_exception
     */
    public static function course_module_viewed_handler(course_module_viewed $event) {
        global $DB;

        $cmid = $event->contextinstanceid;
        $module = $DB->get_record('course_modules', ['id' => $cmid]);
        if (!$module) {
            return;
        }

        $instance = $DB->get_record('mumie', ['id' => $module->instance]);
        if (!$instance) {
            return;
        }

        $timelimit = $instance->timelimit;
        if (isset($timelimit) && $timelimit > 0) {
            $extension = new mumie_duedate_extension($event->userid, $module->instance);
            $extension->load();
            if (!$extension->get_duedate()) {
                $duedate = $event->timecreated + $timelimit;
                $extension->set_duedate($duedate);
                $extension->upsert();
                $calenderservice = new mumie_individual_calendar_service($instance, $event->userid);
                $calenderservice->update();
            }
        }
    }
}
