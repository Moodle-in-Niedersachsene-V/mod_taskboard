<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_taskboard\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_taskboard\local\board_access;
use mod_taskboard\local\board_service;
use mod_taskboard\local\card_data;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the privacy provider.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /**
     * Creates a course board with one card by a student.
     *
     * @return array{0: \stdClass, 1: \stdClass, 2: int, 3: \core\context\module}
     */
    private function setup_card(): array {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $taskboard = $generator->create_module('taskboard', ['course' => $course->id]);
        $this->setUser($student);
        $service = new board_service(board_access::from_cmid((int)$taskboard->cmid));
        $board = $service->resolve_board('');
        $column = $DB->get_field('taskboard_columns', 'id', ['boardid' => $board->id, 'sortorder' => 0]);
        $cardid = $service->add_card($board, (int)$column, new card_data('Private note'));
        return [$taskboard, $student, $cardid, \core\context\module::instance($taskboard->cmid)];
    }

    public function test_export_contains_the_users_card(): void {
        [, $student, , $context] = $this->setup_card();
        $contextlist = provider::get_contexts_for_userid((int)$student->id);
        $this->assertContains((int)$context->id, array_map('intval', $contextlist->get_contextids()));

        provider::export_user_data(new approved_contextlist($student, 'mod_taskboard', [$context->id]));

        $data = writer::with_context($context)->get_data([get_string('privacy:cards', 'mod_taskboard')]);
        $this->assertSame('Private note', $data->cards[0]->title);
    }

    public function test_deleting_user_keeps_card_without_author(): void {
        global $DB;
        [, $student, $cardid, $context] = $this->setup_card();

        provider::delete_data_for_user(new approved_contextlist($student, 'mod_taskboard', [$context->id]));

        $this->assertEquals(0, (int)$DB->get_field('taskboard_cards', 'createdby', ['id' => $cardid]));
    }

    public function test_deleting_context_removes_all_boards(): void {
        global $DB;
        [$taskboard, , , $context] = $this->setup_card();

        provider::delete_data_for_all_users_in_context($context);

        $this->assertSame(0, $DB->count_records('taskboard_boards', ['taskboardid' => $taskboard->id]));
    }
}
