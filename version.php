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
 * This file defines the version of mod_mumie
 *
 * @package mod_mumie
 * @copyright  2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version = 2025070300; // The current module version (Date: YYYYMMDDXX).
$plugin->component = 'mod_mumie'; // Full name of the plugin (used for diagnostics).
$plugin->requires = 2022112800; // 4.1 LTS
$plugin->release = "v1.9.1";
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
    'auth_mumie' => 2025051500,
];
