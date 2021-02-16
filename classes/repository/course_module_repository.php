<?php

namespace mod_mumie;

defined('MOODLE_INTERNAL') || die;

class course_module_repository {

    public static function get_mumie_modules_by_course($courseid){
        global $DB;
        $mumiemoduleid = $DB->get_record("modules", array("name" => 'mumie'))->id;
        debugging( $mumiemoduleid);

        $sql = "SELECT *
        FROM m_course_modules cm
        JOIN m_mumie m ON cm.instance = m.id
        WHERE cm.module = $mumiemoduleid AND cm.course = $courseid";
        return $DB->get_records_sql($sql);
    }

}
