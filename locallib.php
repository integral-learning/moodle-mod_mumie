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
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
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
 * @copyright  2017-2020 integral-learning GmbH (https://www.integral-learning.de/)
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

    /**
     * Check if there are any MUMIE Tasks in the given course.
     *
     * @param int $courseid The course to check
     * @return bool True, if there are MUMIE Tasks in the course
     */
    public static function course_contains_mumie_tasks($courseid) {
        global $DB;
        return count($DB->get_records(MUMIE_TASK_TABLE, array("course" => $courseid))) > 0;
    }

    /**
     * The function is called whenever a MUMIE task is updated or created.
     * If a pending decision regarding gradepools was made, we need to update all other MUMIE Tasks in this course as well.
     * @param stcClass $mumietask The update we are processing
     */
    public static function update_pending_gradepool($mumietask) {
        global $DB;
        $update = false;
        if (!isset($mumie->id)) {
            $update = true;
        } else {
            $oldrecord = $DB->get_record(MUMIE_TASK_TABLE, array('id' => $mumietask->id));
            if ($oldrecord->privategradepool != $mumietask->privategradepool) {
                $update = true;
            }
        }

        if ($update) {
            $tasks = $DB->get_records(MUMIE_TASK_TABLE, array("course" => $mumietask->course));
            foreach ($tasks as $task) {
                if (!isset($task->privategradepool)) {
                    $task->privategradepool = $mumietask->privategradepool;
                    $DB->update_record(MUMIE_TASK_TABLE, $task);
                }
            }
        }
    }

    /**
     * Get a default name for the uploaded MumieTask, if available.
     *
     * The droped MUMIE Task's name is automatically generated and does not look pretty.
     * Search its server for a better name.
     * @param stdClass $uploadedtask
     * @return string
     */
    public static function get_default_name($uploadedtask) {
        global $CFG;
        debugging("getting default name for: " . $uploadedtask->link);
        require_once($CFG->dirroot . '/auth/mumie/classes/mumie_server.php');
        $server = \auth_mumie\mumie_server::get_by_urlprefix($uploadedtask->server);
        $server->load_structure();
        $course = $server->get_course_by_coursefile($uploadedtask->path_to_coursefile);
        $task = $course->get_task_by_link($uploadedtask->link);
        if (is_null($task)) {
            debugging("________task is null!");
            return;
        }
        return $task->get_headline_by_language($uploadedtask->language);
    }

    /**
     * Remove all parameters from a given URL
     *
     * @param string $url
     * @return string $url
     */
    public static function remove_params_from_url($url) {
        if (strpos($url, "?") !== false) {
            $url = substr($url, 0, strpos($url, "?"));
        }
        return $url;
    }

    /**
     * Check whether the used browser is Apple's Safari.
     * 
     * Some functions of this plugin (like embedded MumieTasks)are not supported by Safari. 
     * That's why we need to implement fallback solutions.
     * 
     * @return boolean is Safari?
     */
    public static function is_safari_browser() {
       return self::getBrowser()['name'] == "Apple Safari";
       //return self::getBrowser()['name'] == "Google Chrome";
    }

    public static function getBrowser() { 
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";
      
        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
          $platform = 'linux';
        }elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
          $platform = 'mac';
        }elseif (preg_match('/windows|win32/i', $u_agent)) {
          $platform = 'windows';
        }
      
        // Next get the name of the useragent yes seperately and for good reason
        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)){
          $bname = 'Internet Explorer';
          $ub = "MSIE";
        }elseif(preg_match('/Firefox/i',$u_agent)){
          $bname = 'Mozilla Firefox';
          $ub = "Firefox";
        }elseif(preg_match('/OPR/i',$u_agent)){
          $bname = 'Opera';
          $ub = "Opera";
        }elseif(preg_match('/Chrome/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
          $bname = 'Google Chrome';
          $ub = "Chrome";
        }elseif(preg_match('/Safari/i',$u_agent) && !preg_match('/Edge/i',$u_agent)){
          $bname = 'Apple Safari';
          $ub = "Safari";
        }elseif(preg_match('/Netscape/i',$u_agent)){
          $bname = 'Netscape';
          $ub = "Netscape";
        }elseif(preg_match('/Edge/i',$u_agent)){
          $bname = 'Edge';
          $ub = "Edge";
        }elseif(preg_match('/Trident/i',$u_agent)){
          $bname = 'Internet Explorer';
          $ub = "MSIE";
        }
      
        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
      ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
          // we have no matching number just continue
        }
        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
          //we will have two since we are not using 'other' argument yet
          //see if version is before or after the name
          if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
              $version= $matches['version'][0];
          }else {
              $version= $matches['version'][1];
          }
        }else {
          $version= $matches['version'][0];
        }
      
        // check if we have a number
        if ($version==null || $version=="") {$version="?";}
      
        return array(
          'userAgent' => $u_agent,
          'name'      => $bname,
          'version'   => $version,
          'platform'  => $platform,
          'pattern'    => $pattern
        );
      } 
}
