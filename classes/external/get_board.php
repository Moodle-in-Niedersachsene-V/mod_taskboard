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
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_taskboard\local\board_access;
use mod_taskboard\local\board_query;

/**
 * Returns a board with its columns and cards, or only that nothing changed.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_board extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'boardid' => new external_value(PARAM_INT, 'Board id'),
            'since' => new external_value(PARAM_INT, 'Time of the state the client has, 0 for always'),
        ]);
    }

    /**
     * Returns the board state if it changed since the given time.
     *
     * @param int $cmid Course module id.
     * @param int $boardid Board id.
     * @param int $since Time of the state the client has, 0 for always.
     * @return array
     */
    public static function execute(int $cmid, int $boardid, int $since): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'since' => $since,
        ]);
        $access = board_access::from_cmid($params['cmid']);
        self::validate_context($access->context);
        $access->require_view();
        $board = $access->get_board($params['boardid']);
        if ($params['since'] > 0 && (int)$board->timemodified <= $params['since']) {
            return ['changed' => false, 'timemodified' => (int)$board->timemodified, 'board' => []];
        }
        $query = new board_query($access);
        return ['changed' => true, 'timemodified' => (int)$board->timemodified, 'board' => [$query->export_board($board)]];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'changed' => new external_value(PARAM_BOOL, 'Whether the board changed'),
            'timemodified' => new external_value(PARAM_INT, 'Last change of the board'),
            'board' => new external_multiple_structure(structures::board(), 'The board, only when changed'),
        ]);
    }
}
