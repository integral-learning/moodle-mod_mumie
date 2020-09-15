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
 * A library of functions and constants for the mumie module
 *
 * @package mod_mumie
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/mumie/locallib.php');
define("SSO_TOKEN_TABLE", "auth_mumie_sso_tokens");
define("MUMIE_TASK_TABLE", "mumie");

/**
 * Add a new MUMIE task to the database
 * @param stdClass $mumie instance of MUMIE task to add
 * @param array $mform form data, that has been used to add the new MUMIE task
 * @return int $id id of newly added grade item
 */
function mumie_add_instance($mumie, $mform) {
    global $DB, $CFG;
    $mumie->timecreated = time();
    $mumie->timemodified = $mumie->timecreated;
    $mumie->use_hashed_id = 1;
    mod_mumie\locallib::update_pending_gradepool($mumie);
    $mumie->isgraded = !($mumie->mumie_complete_course ?? 0);
    $mumie->id = $DB->insert_record("mumie", $mumie);
    mumie_grade_item_update($mumie);
    return $mumie->id;
}

/**
 * Updated a MUMIE task in the database
 * @param stdClass $mumie updated instance of MUMIE task
 * @param array $mform form data, that has been used to updated the MUMIE task
 * @return int $id id of updated grade item
 */
function mumie_update_instance($mumie, $mform) {
    global $DB, $CFG;
    $mumie->timemodified = time();
    $mumie->id = $mumie->instance;
    $completiontimeexpected = !empty($mumie->completionexpected) ? $mumie->completionexpected : null;
    \core_completion\api::update_completion_date_event($mumie->coursemodule, 'mumie', $mumie->id, $completiontimeexpected);
    mod_mumie\locallib::update_pending_gradepool($mumie);
    mumie_grade_item_update($mumie);

    return $DB->update_record("mumie", $mumie);
}

/**
 * Delete a MUMIE task from the database
 * @param int $id ID of the MUMIE task that should be deleted
 * @return boolean Success/Failure
 */
function mumie_delete_instance($id) {
    global $DB, $CFG;

    if (!$mumie = $DB->get_record("mumie", array("id" => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('mumie', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'mumie', $id, null);

    return $DB->delete_records("mumie", array("id" => $mumie->id));
}

/**
 * Given a coursemodule object, this function returns the extra
 * information needed to print this activity in various places.
 *
 * @param stdClass $coursemodule
 */
function mumie_get_coursemodule_info($coursemodule) {
    global $DB, $USER, $CFG;

    if (!$mumie = $DB->get_record("mumie", array("id" => $coursemodule->instance))) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $mumie->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('mumie', $mumie, $coursemodule->id, false);
    }

    // If the activity is supposed to open in a new tab, we need to do this right here or moodle won't let us.
    if ($mumie->launchcontainer == MUMIE_LAUNCH_CONTAINER_WINDOW) {
        $info->onclick = "window.open('{$CFG->wwwroot}/mod/mumie/view.php?id={$coursemodule->id}'); return false;";
    }

    return $info;
}

/**
 * Add information about potential due dates or pending decisions to the list view
 *
 * @param cm_info $cm
 */
function mumie_cm_info_view(cm_info $cm) {
    global $CFG, $DB;

    $date = new DateTime("now", core_date::get_user_timezone_object());
    $mumie = $DB->get_record('mumie', array('id' => $cm->instance));
    $info = '';
    if ($mumie->duedate) {
        $info .= ' ' .
            html_writer::tag('p', get_string('mumie_due_date', 'mod_mumie'), array('class' => 'tag-info tag'))
            . html_writer::tag(
                'span',
                strftime(
                    get_string('strftimedaydatetime', 'langconfig'),
                    $mumie->duedate
                ),
                array('style' => 'margin-left: 1em')
            );
    }
    if (!isset($mumie->privategradepool)) {
        $info .= ' ' .
            html_writer::tag('p', get_string('mumie_tag_disabled', 'mod_mumie'), array('class' => 'tag-warning tag'))
            . html_writer::tag(
                'span',
                get_string('mumie_tag_disabled_help', 'mod_mumie'),
                array('style' => 'margin-left: 1em')
            );
    }
    $cm->set_after_link($info);
}
/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function mumie_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Create grade item for given mumie task
 *
 * @category grade
 * @param object $mumie object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s)
 * @return int 0 if ok, error code otherwise
 */
function mumie_grade_item_update($mumie, $grades = null) {
    global $CFG;
    if (!$mumie->isgraded) {
        return false;
    }
    require_once($CFG->libdir . '/gradelib.php');
    if (array_key_exists('cmidnumber', $mumie)) {
        $params = array('itemname' => $mumie->name, 'idnumber' => $mumie->cmidnumber);
    } else {
        $params = array('itemname' => $mumie->name);
    }
    if (isset($mumie->points) && $mumie->points > 0) {
        $params['grademax'] = $mumie->points;
        $params['grademin'] = 0;
    }
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['multfactor'] = $mumie->points / 100;

    return grade_update('mod/mumie', $mumie->course, 'mod', 'mumie', $mumie->id, 0, $grades, $params);
}

/**
 * Update activity grades
 *
 * @param stdClass $mumie The mumie instance
 * @param int      $userid Specific user only, 0 means all.
 * @param bool     $nullifnone Not used
 */
function mumie_update_grades($mumie, $userid = 0, $nullifnone = true) {
    if (!isset($mumie->privategradepool)) {
        return;
    }
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/mod/mumie/gradesync.php');

    mumie_grade_item_update($mumie, mod_mumie\gradesync::get_mumie_grades($mumie, $userid));
}

/**
 * Hook used to updated grades for MUMIE tasks, whenever a gradebook is opened
 * @return void
 */
function mumie_before_standard_top_of_body_html() {
    global $PAGE, $CFG;

    if (!strpos($PAGE->url, '/grade/report/')) {
        return "";
    }

    require_once($CFG->dirroot . '/mod/mumie/gradesync.php');
    mod_mumie\gradesync::update();

    return "";
}

/**
 * Obtains the automatic completion state for this MUMIE task
 *
 * This is a code fragment copied from mod_quiz version 2018051400
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function mumie_get_completion_state($course, $cm, $userid, $type) {
    global $DB, $CFG;
    $mumie = $DB->get_record('mumie', array('id' => $cm->instance), '*', MUST_EXIST);

    if ($mumie->completionpass) {
        require_once($CFG->libdir . '/gradelib.php');
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
            'itemmodule' => 'mumie', 'iteminstance' => $cm->instance, 'outcomeid' => null));

        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, array($userid), false);
            if (!empty($grades[$userid])) {
                return $grades[$userid]->is_passed($item);
            }
        }
    }
    return false;
}

/**
 * Register the ability to handle drag and drop of datatransfertype mumie/json
 * @return array containing details of the files / types the mod can handle
 */
function mumie_dndupload_register() {
    return array(
        'addtypes' => array(
            array(
                'identifier' => 'mumie/json', 'datatransfertypes' => array('mumie/json', 'mumie/json'),
                'addmessage' => get_string('dnd_addmessage', 'mod_mumie'),
                'namemessage' => '',
                'priority' => 1)
            ),
        'types' => array(
            array(
                'identifier' => 'mumie/json',
                'message' => get_string('dndupload_message', 'mod_mumie'),
                'noname' => true),
            )
    );
}


/**
 * Handle content that has been uploaded
 * @param object $uploadinfo details of the content that has been uploaded
 * @return int instance id of the newly created mod
 */
function mumie_dndupload_handle($uploadinfo) {
    global $CFG, $COURSE, $USER;

    $context = context_module::instance($uploadinfo->coursemodule);
    $upload = json_decode(clean_param($uploadinfo->content, PARAM_RAW));
    require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
    if (!isset($upload->link) || !isset($upload->path_to_coursefile)
        || !isset($upload->language) || !isset($upload->name) || !isset($upload->server) || !isset($upload->course)) {
        throw new moodle_exception('parameter_missing', 'mod_mumie');
    }
    $server = new auth_mumie\mumie_server();
    $server->set_urlprefix($upload->server);
    if (!$server->is_valid_mumie_server()) {
        throw new moodle_exception('mumie_form_server_not_existing', 'auth_mumie');
    }
    if (!$server->config_exists_for_url()) {
        if (has_capability("auth/mumie:addserver", \context_course::instance($COURSE->id), $USER)) {
            $server->set_name($server->get_urlprefix());
            $server->upsert();
        } else {
            throw new moodle_exception(get_string('server_config_missing', 'mod_mumie', $server->get_urlprefix()));
        }
    }
    $mumie = new stdClass();
    $mumie->taskurl = $upload->link . '?lang=' . $upload->language;
    $mumie->mumie_coursefile = $upload->path_to_coursefile;
    $mumie->language = $upload->language;
    $mumie->course = $uploadinfo->course->id;
    $mumie->server = $server->get_urlprefix();
    $mumie->mumie_course = $upload->course;
    $mumie->intro = '';
    $mumie->points = 100;
    $mumie->name = mod_mumie\locallib::get_default_name($upload) ?? $upload->name;
    global $DB;
    $exitingtasks = array_values(
        $DB->get_records(MUMIE_TASK_TABLE, array("course" => $COURSE->id))
    );
    if (count($exitingtasks) > 0) {
        $mumie->privategradepool = $exitingtasks[0]->privategradepool;
    }
    return mumie_add_instance($mumie, null);
}
