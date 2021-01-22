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
 * This file describes a class used to display a form to create and edit due date extensions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * This file describes a class used to display a form to create and edit due date extensions.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duedate_form extends moodleform {
    /**
     * Define fields and default values for the mumie server form
     * @return void
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('date_time_selector', 'duedate', get_string("mumie_due_date", "mod_mumie"));

        $mform->addElement("hidden", "mumie");
        $mform->setType("mumie", PARAM_INT);

        $mform->addElement("hidden", "userid");
        $mform->setType("userid", PARAM_INT);

        $mform->addElement("hidden", "id");
        $mform->setType("id", PARAM_INT);
    }

    /**
     * Validate the form data
     * @param array $data form data
     * @param array $files files uploaded
     * @return array associative array of errors
     */
    public function validation($data, $files) {
        $errors = array();
        if ($data['duedate']) {
            if (time() - $data['duedate'] > 0) {
                $errors['duedate'] = get_string('mumie_form_due_date_must_be_future', 'mod_mumie');
            }
        }
        return $errors;
    }
}