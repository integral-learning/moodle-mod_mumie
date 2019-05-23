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

require_once ($CFG->dirroot . '/mod/mumie/lib.php');

global $CFG;
global $DB;

define("MUMIE_LAUNCH_CONTAINER_WINDOW", 0);
define("MUMIE_LAUNCH_CONTAINER_EMBEDDED", 1);
define("MUMIE_SERVER_TABLE_NAME", "mumie_servers");

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
     * get all MUMIE servers that have been added
     * @return array of all configured MUMIE servers
     */
    public static function get_all_mumie_servers() {
        global $DB;
        return $DB->get_records(MUMIE_SERVER_TABLE_NAME);
    }

    /**
     * Get a mumie server from the database
     * @param int   $id id of MUMIE server to get from the database
     * @return stdClass
     */
    public static function get_mumie_server($id) {
        global $DB;
        return $DB->get_record(MUMIE_SERVER_TABLE_NAME, array("id" => $id));
    }

    /**
     * Find a MUMIE server configuration by name
     * @param string $name name of MUMIE server to get from the database
     * @return stdClass
     */
    public static function get_mumie_server_by_name($name) {
        global $DB;
        return $DB->get_record(MUMIE_SERVER_TABLE_NAME, array("name" => $name));
    }

    /**
     * Delete a MUMIE server configuration from the database
     * @param int $id id of MUMIE server to delete
     * @return void
     */
    public static function delete_mumie_server($id) {
        global $DB;
        $DB->delete_records(MUMIE_SERVER_TABLE_NAME, array("id" => $id));
    }

    /**
     * Update an existing MUMIE server configuration in the database
     * @param stdClass $mumieserver updated mumie server
     * @return Int id of MUMIE server
     */
    public static function update_mumie_server($mumieserver) {
        global $DB;
        $mumieserver->url_prefix = self::get_processed_server_url($mumieserver->url_prefix);

        $DB->update_record(MUMIE_SERVER_TABLE_NAME, (array) $mumieserver);
    }

    /**
     * Add a new MUMIE server configuration to the database
     * @param stdClass $mumieserver new mumie server
     * @return int id of new mumie server
     */
    public static function insert_mumie_server($mumieserver) {
        global $DB;
        $mumieserver->url_prefix = self::get_processed_server_url($mumieserver->url_prefix);

        $DB->insert_record(MUMIE_SERVER_TABLE_NAME, (array) $mumieserver);
    }

    /**
     * Get instance of mumie task with its id
     * @param int $id id of the mumie task
     * @return stdClass instance of mumie task
     */
    public static function get_mumie_task($id) {
        global $DB;
        return $DB->get_record(MUMIE_TASK_TABLE_NAME, array('id' => $id));
    }

    /**
     * Get a list of all courses that are availabe on the given mumie server
     * @param string $url of the mumie server
     * @return array all available courses
     */
    public static function get_available_courses($url) {

        $url = self::get_processed_server_url($url);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url . "public/courses-and-tasks",
            CURLOPT_USERAGENT => 'Codular Sample cURL Request',
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * Get a list of all courses that are availabe any of the configured mumie servers
     * @return array all available courses for all configured mumie servers
     */
    public static function get_available_courses_for_all_servers() {
        $coursesforserver = array();
        foreach (self::get_all_mumie_servers() as $server) {
            $coursesforserver[$server->name] = self::get_available_courses($server->url_prefix);
        }

        return $coursesforserver;
    }

    /**
     * Make sure that the url ends with slash
     *
     * @param string $url unprocessed url
     * @return string url that ends with a slash
     */
    public static function get_processed_server_url($url) {
        return (substr($url, -1) == '/' ? $url : $url . '/');
    }
}
