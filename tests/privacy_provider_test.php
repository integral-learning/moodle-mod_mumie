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
 * privacy provider tests.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_mumie\privacy\provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider test class.
 *
 * @package mod_mumie
 * @copyright  2017-2021 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mumie_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    
    public function test_get_contexts_for_userid_no_data() {
        global $USER;
        $this->setAdminUser();
        $contextlist = provider::get_contexts_for_userid($USER->id);
        $this->assertCount(0, $contextlist);
    }

    public function test_get_contexts_for_userid_with_data() {

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $anotheruser = $this->getDataGenerator()->create_user();

        $mumie = $this->create_test_mumie_cm($course);
        $anothermumie = $this->create_test_mumie_cm($course);

        $this->add_duedate($mumie, $user);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        // Make sure that there is no data for the control group.
        $contextlist = provider::get_contexts_for_userid($anotheruser->id);
        $this->assertCount(0, $contextlist);
    }

    public function test_get_users_in_context() {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $m1 = $this->create_test_mumie_cm($course1);
        $m2 = $this->create_test_mumie_cm($course1);
        $m3 = $this->create_test_mumie_cm($course2);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->add_duedate($m1, $user1);
        $this->add_duedate($m2, $user2);
        $this->add_duedate($m2, $user1);

        $context1 = context_module::instance($m1->cmid);
        $context2 = context_module::instance($m2->cmid);
        $context3 = context_module::instance($m3->cmid);

        // Test with one user.
        $userlist1 = new \core_privacy\local\request\userlist($context1, 'mod_mumie');
        provider::get_users_in_context($userlist1);
        $this->assertEquals([$user1->id], $userlist1->get_userids());

        // Test with both users.
        $userlist2 = new \core_privacy\local\request\userlist($context2, 'mod_mumie');
        provider::get_users_in_context($userlist2);
        $this->assertEquals([$user1->id, $user2->id], $userlist2->get_userids());

        // Test with no data.
        $userlist3 = new \core_privacy\local\request\userlist($context3, 'mod_mumie');
        provider::get_users_in_context($userlist3);
        $this->assertEquals([], $userlist3->get_userids());
    }

    public function test_delete_data_for_users() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $m1 = $this->create_test_mumie_cm($course1);
        $m2 = $this->create_test_mumie_cm($course1);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->add_duedate($m1, $user1);
        $this->add_duedate($m2, $user1);
        $this->add_duedate($m1, $user2);
        $this->add_duedate($m1, $user3);

        $context1 = context_module::instance($m1->cmid);
        $context2 = context_module::instance($m2->cmid);

        $approveduserlist = new \core_privacy\local\request\approved_userlist(
            $context1,
            'mod_mumie',
            [$user1->id, $user3->id]
        );
        provider::delete_data_for_users($approveduserlist);

        // Duedate we set for user1 in m2 should still exist.
        $this->assertEquals(
            [$user1->id],
            $DB->get_fieldset_select('mumie_duedate', 'userid', 'mumie = ?', [$m2->id])
        );

        // We didn't delete data for user2 in m1 so duedate should still exist.
        $this->assertEquals(
            [$user2->id],
            $DB->get_fieldset_select('mumie_duedate', 'userid', 'mumie = ?', [$m1->id])
        );
    }

    function test_delete_data_for_all_users_in_context() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $m1 = $this->create_test_mumie_cm($course1);
        $m2 = $this->create_test_mumie_cm($course1);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->add_duedate($m1, $user1);
        $this->add_duedate($m2, $user1);
        $this->add_duedate($m1, $user2);
        $this->add_duedate($m1, $user3);

        $context1 = context_module::instance($m1->cmid);
        $context2 = context_module::instance($m2->cmid);

        provider::delete_data_for_all_users_in_context($context1);

        // There should be no data for m1 left.
        $this->assertEmpty($DB->get_records('mumie_duedate', array('mumie' => $m1->id)));

        // The data for m2 should still exist.
        $this->assertCount(1, $DB->get_records('mumie_duedate', array('mumie' => $m2->id)));

    }

    public function test_delete_data_for_user() {
        global $DB;
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $m1 = $this->create_test_mumie_cm($course1);
        $m2 = $this->create_test_mumie_cm($course1);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->add_duedate($m1, $user1);
        $this->add_duedate($m2, $user1);
        $this->add_duedate($m1, $user2);
        $this->add_duedate($m1, $user3);

        $context1 = context_module::instance($m1->cmid);
        $context2 = context_module::instance($m2->cmid);

        $contextlist = new approved_contextlist(
            $user1,
            'mumie',
            [context_system::instance()->id, $context1->id, $context2->id]
        );
        provider::delete_data_for_user($contextlist);

        $this->assertEquals(
            [$user2->id, $user3->id],
            $DB->get_fieldset_select('mumie_duedate', 'userid', 'mumie = ?', [$m1->id])
        );

        $this->assertEmpty(
            $DB->get_fieldset_select('mumie_duedate', 'userid', 'mumie = ?', [$m2->id])
        );

    }

    public function test_export_user_data_no_data() {
        $user1 = $this->getDataGenerator()->create_user();

        $approvedcontextlist = new \core_privacy\tests\request\approved_contextlist(
            \core_user::get_user($user1->id),
            'mod_mumie',
            []
        );
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data_in_any_context());
    }

    public function test_export_user_data() {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $m1 = $this->create_test_mumie_cm($course1);
        $m2 = $this->create_test_mumie_cm($course1);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->add_duedate($m1, $user1);
        $this->add_duedate($m2, $user1);
        $this->add_duedate($m1, $user2);

        $context1 = context_module::instance($m1->cmid);
        $context2 = context_module::instance($m2->cmid);

        $this->export_context_data_for_user($user1->id, $context1, 'mod_mumie');
        $writer1 = writer::with_context($context1);
        $this->assertTrue($writer1->has_any_data());

        // There shouldn't be any data for user2 in context2.
        $this->export_context_data_for_user($user2->id, $context2, 'mod_mumie');
        $writer2 = writer::with_context($context2);
        $this->assertFalse($writer2->has_any_data());
    }

    public function setUp() {
        $this->resetAfterTest(true);
    }

    protected function create_test_mumie_cm($course) {
        global $DB;
        return $this->getDataGenerator()->create_module('mumie',['course' => $course->id]);
    }

    protected function add_duedate($mumie, $user) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/mumie/classes/mumie_duedate_extension.php');

        $duedate = new mod_mumie\mumie_duedate_extension($user->id, $mumie->id);
        $duedate->set_duedate(1000);
        $duedate->upsert();
    }

}
