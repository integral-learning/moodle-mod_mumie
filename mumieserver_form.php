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
 * This moodle form is used to insert or update MumieServer in the database
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->dirroot . '/mod/mumie/locallib.php');

/**
 * This moodle form is used to insert or update MumieServer in the database
 *
 * @package mod_mumie
 * @copyright  2018
 * @author     Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mumieserver_form extends moodleform {
    /**
     * Define fields and default values for the mumie server form
     * @return void
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('mumie_server_name', 'mod_mumie'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton("name", 'mumie_server_name', 'mumie');

        $mform->addElement('text', 'url_prefix', get_string('mumie_url_prefix', 'mod_mumie'));
        $mform->setType('url_prefix', PARAM_NOTAGS);
        $mform->addHelpButton("url_prefix", 'mumie_url_prefix', 'mumie');

        $mform->addElement('hidden', "id", 0);
        $mform->setType("id", PARAM_INT);
        $mform->setDefault("id", 0);

        $mform->addElement('hidden', 'notifyParent', false);
        $mform->setType("notifyParent", PARAM_BOOL);
    }

    /**
     * Valdiate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files) {
        global $DB;
        $errors = array();
        if (mod_mumie\locallib::get_available_courses($data["url_prefix"])["courses"] == null) {
            $errors["url_prefix"] = get_string('mumie_form_server_not_existing', 'mod_mumie');
        }

        // Array containing all servers with the given url_prefix.
        $serverbyprefix = $DB->get_records(
            MUMIE_SERVER_TABLE_NAME,
            array("url_prefix" => mod_mumie\locallib::get_processed_server_url($data["url_prefix"]))
        );
        $serverbyname = $DB->get_records(MUMIE_SERVER_TABLE_NAME, array("name" => $data["name"]));

        if (strlen($data["name"]) == 0) {
            $errors["name"] = get_string('mumie_form_required', 'mod_mumie');
        }

        if (count($serverbyname) > 0 && !$data["id"] > 0) {
            $errors["name"] = get_string('mumie_form_already_existing_name', "mod_mumie");
        }
        if (count($serverbyname) > 0 && $data["id"] > 0 && array_values($serverbyname)[0]->id != $data["id"]) {
            $errors["name"] = get_string('mumie_form_already_existing_name', "mod_mumie");
        }

        if (strlen($data["url_prefix"]) == 0) {
            $errors["url_prefix"] = get_string('mumie_form_required', 'mod_mumie');
        }

        /* url_prefix is a unique attribute. If a new server is added (id = default value),
        there mustn't be a server with this property in the database
         */
        if (count($serverbyprefix) > 0 && !$data["id"] > 0) {
            $errors["url_prefix"] = get_string('mumie_form_already_existing_config', "mod_mumie");
        }

        /* url_prefix is a unique attribute. If an existing server is edited (id>0), make sure,
        that there is no other server(a server with a different id) with the same property in the database
         */
        if (count($serverbyprefix) > 0 && $data["id"] > 0 && array_values($serverbyprefix)[0]->id != $data["id"]) {
            $errors["url_prefix"] = get_string('mumie_form_already_existing_config', "mod_mumie");
        }

        return $errors;
    }
}
