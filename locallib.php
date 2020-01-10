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
 * Libary of internal functions used in mod_mumie
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/mumie/lib.php');

global $CFG;
global $DB;

define("MUMIE_LAUNCH_CONTAINER_WINDOW", 0);
define("MUMIE_LAUNCH_CONTAINER_EMBEDDED", 1);

/**
 * Libary of internal functions used in mod_mumie
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib {

    /**
     * Get instance of mumie task with its id
     * @param int $id id of the mumie task
     * @return stdClass instance of mumie task
     */
    public static function get_mumie_task($id) {
        global $DB;
        return $DB->get_record(MUMIE_TASK_TABLE_NAME, array('id' => $id));
    }
}
