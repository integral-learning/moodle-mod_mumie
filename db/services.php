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
 * MUMIE task external functions and service definitions
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'mod_mumie_submit_mumieserver_form' => array(
        'classname' => 'mod_mumie_external',
        'methodname' => 'submit_mumieserver_form',
        'classpath' => 'mod/mumie/externallib.php',
        'description' => 'Creates a Mumie server from submitted form data',
        'ajax' => true,
        'type' => 'write',
    ),

    'mod_mumie_get_available_courses' => array(
        'classname' => 'mod_mumie_external',
        'methodname' => 'get_available_courses',
        'classpath' => 'mod/mumie/externallib.php',
        'description' => 'get all Mumie courses that are available for this instutition',
        'ajax' => true,
        'type' => 'read',
    ),
);