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
 * This file keeps track of upgrades to the mod_mumie module
 *
 * @package     mod_mumie
 * @copyright   2017-2025 integral-learning GmbH (https://www.integral-learning.de/)
 * @author      Yannic Lapawczyk (yannic.lapawczyk@integral-learning.de)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => 'mod_mumie\hook\output\before_standard_top_of_body_html_generation::callback',
        'priority' => 0,
    ],
];
