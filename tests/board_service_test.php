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

namespace mod_taskboard;

use mod_taskboard\local\board_access;
use mod_taskboard\local\board_query;
use mod_taskboard\local\board_service;
use mod_taskboard\local\card_data;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the board service and the access rules.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(board_service::class)]
#[CoversClass(board_access::class)]
#[CoversClass(board_query::class)]
final class board_service_test extends \advanced_testcase {
    /**
     * Creates a course with a task board, a teacher and two students.
     *
     * @param array $settings Instance settings.
     * @return array{0: \stdClass, 1: \stdClass, 2: \stdClass, 3: \stdClass, 4: \stdClass}
     */
    private function setup_board(array $settings = []): array {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $student = $generator->create_and_enrol($course, 'student');
        $other = $generator->create_and_enrol($course, 'student');
        $taskboard = $generator->create_module('taskboard', ['course' => $course->id] + $settings);
        return [$course, $taskboard, $teacher, $student, $other];
    }

    /**
     * Returns the access object for a user.
     *
     * @param \stdClass $taskboard The instance with cmid.
     * @param \stdClass $user The user.
     * @return board_access
     */
    private function access_for(\stdClass $taskboard, \stdClass $user): board_access {
        $this->setUser($user);
        return board_access::from_cmid((int)$taskboard->cmid);
    }

    /**
     * Returns the column ids of a board in order.
     *
     * @param \stdClass $board The board.
     * @return int[]
     */
    private function column_ids(\stdClass $board): array {
        global $DB;
        return array_map('intval', array_keys($DB->get_records('taskboard_columns', ['boardid' => $board->id], 'sortorder')));
    }

    public function test_new_board_copies_template_columns_and_cards(): void {
        global $DB;
        [, $taskboard, $teacher, $student] = $this->setup_board();
        $teacheraccess = $this->access_for($taskboard, $teacher);
        $teacherservice = new board_service($teacheraccess);
        $template = $teacherservice->get_or_create_board(0, 0, true);
        $columns = $this->column_ids($template);
        $teacherservice->add_card($template, $columns[0], new card_data('Prepared task', checks: ['First step']));

        $studentservice = new board_service($this->access_for($taskboard, $student));
        $board = $studentservice->resolve_board('');

        $this->assertEquals(0, (int)$board->istemplate);
        $this->assertCount(3, $this->column_ids($board));
        $cards = $DB->get_records('taskboard_cards', ['boardid' => $board->id]);
        $this->assertCount(1, $cards);
        $this->assertSame('Prepared task', reset($cards)->title);
        $this->assertSame(1, $DB->count_records('taskboard_checks', ['cardid' => reset($cards)->id]));
    }

    public function test_links_are_saved_and_copied_from_template(): void {
        global $DB;
        [, $taskboard, $teacher, $student] = $this->setup_board();
        $teacherservice = new board_service($this->access_for($taskboard, $teacher));
        $template = $teacherservice->get_or_create_board(0, 0, true);
        $links = card_data::links_from_text("Weather data | https://example.org/weather\nhttps://example.org/map\nnot a link");
        $teacherservice->add_card($template, $this->column_ids($template)[0], new card_data('With links', links: $links));

        $board = (new board_service($this->access_for($taskboard, $student)))->resolve_board('');
        $cardid = $DB->get_field('taskboard_cards', 'id', ['boardid' => $board->id]);
        $stored = array_values($DB->get_records('taskboard_links', ['cardid' => $cardid], 'sortorder'));

        $this->assertCount(2, $stored);
        $this->assertSame('Weather data', $stored[0]->title);
        $this->assertSame('https://example.org/map', $stored[1]->url);
    }

    public function test_move_card_reorders_both_columns(): void {
        global $DB;
        [, $taskboard, , $student] = $this->setup_board();
        $service = new board_service($this->access_for($taskboard, $student));
        $board = $service->resolve_board('');
        [$todo, $doing] = $this->column_ids($board);
        $first = $service->add_card($board, $todo, new card_data('First'));
        $second = $service->add_card($board, $todo, new card_data('Second'));
        $third = $service->add_card($board, $doing, new card_data('Third'));

        $card = $DB->get_record('taskboard_cards', ['id' => $first]);
        $service->move_card($card, $doing, 0);

        $doingorder = $DB->get_fieldset_sql(
            'SELECT id FROM {taskboard_cards} WHERE columnid = ? ORDER BY sortorder',
            [$doing]
        );
        $this->assertEquals([$first, $third], array_map('intval', $doingorder));
        $this->assertEquals(0, (int)$DB->get_field('taskboard_cards', 'sortorder', ['id' => $second]));
    }

    public function test_student_with_own_rights_cannot_edit_other_cards(): void {
        global $DB;
        [, $taskboard, , $student, $other] = $this->setup_board(['studentrights' => 'own']);
        $service = new board_service($this->access_for($taskboard, $student));
        $board = $service->resolve_board('');
        $cardid = $service->add_card($board, $this->column_ids($board)[0], new card_data('Mine'));
        $card = $DB->get_record('taskboard_cards', ['id' => $cardid]);

        $otheraccess = $this->access_for($taskboard, $other);

        $this->assertFalse($otheraccess->can_edit_card($board, $card));
        $this->assertTrue($otheraccess->can_move_card($board, $card));
    }

    public function test_students_with_move_rights_cannot_add_cards(): void {
        [, $taskboard, , $student] = $this->setup_board(['studentrights' => 'move']);
        $access = $this->access_for($taskboard, $student);
        $board = (new board_service($access))->resolve_board('');

        $this->assertFalse($access->can_add_card($board));
        $this->assertFalse($access->can_manage_columns($board));
    }

    public function test_pending_card_is_hidden_from_other_students(): void {
        global $DB;
        [, $taskboard, , $student, $other] = $this->setup_board(['approval' => 1]);
        $service = new board_service($this->access_for($taskboard, $student));
        $board = $service->resolve_board('');
        $cardid = $service->add_card($board, $this->column_ids($board)[0], new card_data('Waiting'));
        $card = $DB->get_record('taskboard_cards', ['id' => $cardid]);

        $this->assertEquals(0, (int)$card->approved);
        $this->assertFalse($this->access_for($taskboard, $other)->can_see_card($card));
        $this->assertTrue($this->access_for($taskboard, $student)->can_see_card($card));
    }

    public function test_group_board_is_closed_to_non_members(): void {
        [$course, $taskboard, , $student, $other] = $this->setup_board(['boardtype' => 1]);
        $generator = $this->getDataGenerator();
        $group = $generator->create_group(['courseid' => $course->id]);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $board = (new board_service($this->access_for($taskboard, $student)))->resolve_board('');

        $this->assertEquals((int)$group->id, (int)$board->groupid);
        $this->assertFalse($this->access_for($taskboard, $other)->can_view_board($board));
    }

    public function test_done_cards_count_for_author_and_assignee(): void {
        global $DB;
        [, $taskboard, , $student, $other] = $this->setup_board();
        $service = new board_service($this->access_for($taskboard, $student));
        $board = $service->resolve_board('');
        $columns = $this->column_ids($board);
        $cardid = $service->add_card($board, $columns[0], new card_data('Shared', assignees: [(int)$other->id]));
        $service->move_card($DB->get_record('taskboard_cards', ['id' => $cardid]), end($columns), 0);

        $this->assertSame(1, board_query::count_done_cards((int)$taskboard->id, (int)$student->id));
        $this->assertSame(1, board_query::count_done_cards((int)$taskboard->id, (int)$other->id));
    }

    public function test_delete_column_removes_its_cards(): void {
        global $DB;
        [, $taskboard, $teacher] = $this->setup_board();
        $service = new board_service($this->access_for($taskboard, $teacher));
        $board = $service->resolve_board('');
        $columns = $this->column_ids($board);
        $service->add_card($board, $columns[0], new card_data('Gone'));

        $service->delete_column($DB->get_record('taskboard_columns', ['id' => $columns[0]]));

        $this->assertSame(0, $DB->count_records('taskboard_cards', ['boardid' => $board->id]));
        $this->assertCount(2, $this->column_ids($board));
    }
}
