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
 * This page lists all instances of MUMIE task in a paricular course
 *
 * This script is adapted from mod_lti version 2018051400
 *
 * @package mod_mumie
 * @copyright  2019 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/mumie/index.php', array('id' => $course->id));
$pagetitle = strip_tags($course->shortname . ': ' . get_string("modulenameplural", "mumie"));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string("modulenameplural", "mumie"));

// Get all the appropriate data.
if (!$mumies = get_all_instances_in_course("mumie", $course)) {
    notice(get_string('nomumieinstance', 'mumie'), "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances (your module will probably extend this).
$timenow = time();
$strname = get_string("name");
$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = array($strsectionname, $strname, get_string("mumie_due_date", "mod_mumie"));
    $table->align = array("center", "left");
} else {
    $table->head = array($strname, get_string("mumie_due_date", "mod_mumie"));
}

foreach ($mumies as $mumie) {
    if (!$mumie->visible) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$mumie->coursemodule\">$mumie->name</a>";
    } else {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$mumie->coursemodule\">$mumie->name</a>";
    }

    if ($mumie->duedate) {
        $duedate = strftime(get_string('strftimedaydatetime', 'langconfig'), $mumie->duedate);
    } else {
        $duedate = "";
    }

    if ($usesections) {
        $table->data[] = array(get_section_name($course, $mumie->section), $link, $duedate);
    } else {
        $table->data[] = array($link, $duedate);
    }
}

echo "<br />";

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
