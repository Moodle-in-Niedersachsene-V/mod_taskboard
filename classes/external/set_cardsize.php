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
use core_external\external_single_structure;
use core_external\external_value;
use mod_taskboard\local\board_access;
use mod_taskboard\local\presets;

/**
 * Stores the card size the user prefers for this activity.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_cardsize extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'size' => new external_value(PARAM_ALPHA, 'Card size: auto, s, m or l'),
        ]);
    }

    /**
     * Stores the card size.
     *
     * @param int $cmid Course module id.
     * @param string $size Card size: auto, s, m or l.
     * @return array
     */
    public static function execute(int $cmid, string $size): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'size' => $size,
        ]);
        $access = board_access::from_cmid($params['cmid']);
        self::validate_context($access->context);
        $access->require_view();
        if (!$access->can_change_cardsize()) {
            throw new \moodle_exception('nopermissioncardsize', 'mod_taskboard');
        }
        if (!in_array($params['size'], presets::CARDSIZES, true)) {
            throw new \invalid_parameter_exception('Unknown card size.');
        }
        set_user_preference('mod_taskboard_cardsize_' . $access->cm->id, $params['size']);
        return ['size' => $params['size']];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'size' => new external_value(PARAM_ALPHA, 'The stored card size'),
        ]);
    }
}
