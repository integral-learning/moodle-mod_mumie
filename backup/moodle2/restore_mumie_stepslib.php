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
 * Defines all restore steps that will be used by the restore_mumie_activity_task
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one mumie activity
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mumie_activity_structure_step extends restore_activity_structure_step {

    /**
     * define the structure for restoration process
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('mumie', '/activity/mumie');
        $paths[] = new restore_path_element('serverconfig', '/activity/mumie/serverconfig');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore instances of MUMIE tasks
     * @param mixed $data parsed from the backup file
     */
    protected function process_mumie($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->use_hashed_id = 1;

        if ($existingtask = array_values($DB->get_records('mumie', array('course' => $data->course)))[0]) {
            $data->privategradepool = $existingtask->privategradepool;
        } else {
            $data->privategradepool = $data->privategradepool ?? 0;
        }

        $newitemid = $DB->insert_record('mumie', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore instances of MUMIE servers, if there is no conflict
     *
     * @param mixed $data parsed from the backup file
     */
    protected function process_serverconfig($data) {
        global $DB;

        $data = (object) $data;

        /*
        Only insert record, if there are no configurations for name or prefix!
        This means that a missing server is not always automatically restored
        and needs to be added manually before the task can be edited
         */
        $recordnameexists = $DB->record_exists("auth_mumie_servers", array("name" => $data->name));
        $recordurlexists = $DB->record_exists("auth_mumie_servers", array("url_prefix" => $data->url_prefix));

        if (!$recordnameexists && !$recordurlexists) {
            $DB->insert_record('auth_mumie_servers', $data);
        }
    }
}