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
 * Script to delete a mumie due date extension from the database.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');
require_once($CFG->dirroot . '/mod/mumie/classes/mumie_calendar_service/mumie_individual_calendar_service.php');
require_login(null, false);

$duedateid = required_param('duedateid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

$returnurl = new \moodle_url('/mod/mumie/view.php', array("id" => $cmid, "action" => "grading"));
require_capability('mod/mumie:revokeduedateextension', context_system::instance());
$extension = mod_mumie\mumie_duedate_extension::load_by_id($duedateid);
$extension->delete();
$calendarservice = new mod_mumie\mumie_individual_calendar_service($extension->get_mumie(), $extension->get_userid());
$calendarservice->update();

redirect($returnurl);
