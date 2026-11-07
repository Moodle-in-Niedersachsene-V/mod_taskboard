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

namespace mod_taskboard\external;

use core_external\external_api;
use mod_taskboard\local\board_access;
use mod_taskboard\local\board_service;
use mod_taskboard\local\card_data;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the move_card and get_board external functions.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(move_card::class)]
#[CoversClass(get_board::class)]
final class move_card_test extends \advanced_testcase {
    public function test_member_moves_card_and_sees_it_in_new_column(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $taskboard = $generator->create_module('taskboard', ['course' => $course->id]);
        $this->setUser($student);
        $service = new board_service(board_access::from_cmid((int)$taskboard->cmid));
        $board = $service->resolve_board('');
        $columns = array_keys($DB->get_records('taskboard_columns', ['boardid' => $board->id], 'sortorder'));
        $cardid = $service->add_card($board, (int)$columns[0], new card_data('Card'));

        $result = move_card::execute((int)$taskboard->cmid, $cardid, (int)$columns[1], 0);
        external_api::clean_returnvalue(move_card::execute_returns(), $result);
        $state = get_board::execute((int)$taskboard->cmid, (int)$board->id, 0);
        $state = external_api::clean_returnvalue(get_board::execute_returns(), $state);

        $this->assertEquals((int)$columns[1], (int)$DB->get_field('taskboard_cards', 'columnid', ['id' => $cardid]));
        $this->assertSame($cardid, $state['board'][0]['columns'][1]['cards'][0]['id']);
    }

    public function test_user_without_capability_cannot_move(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $outsider = $generator->create_user();
        $taskboard = $generator->create_module('taskboard', ['course' => $course->id]);
        $this->setUser($student);
        $service = new board_service(board_access::from_cmid((int)$taskboard->cmid));
        $board = $service->resolve_board('');
        $columns = array_keys($DB->get_records('taskboard_columns', ['boardid' => $board->id], 'sortorder'));
        $cardid = $service->add_card($board, (int)$columns[0], new card_data('Card'));

        $this->setUser($outsider);
        $this->expectException(\require_login_exception::class);
        try {
            move_card::execute((int)$taskboard->cmid, $cardid, (int)$columns[1], 0);
        } finally {
            $this->assertEquals((int)$columns[0], (int)$DB->get_field('taskboard_cards', 'columnid', ['id' => $cardid]));
        }
    }
}
