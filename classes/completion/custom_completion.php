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

declare(strict_types=1);

namespace mod_taskboard\completion;

use core_completion\activity_custom_completion;
use mod_taskboard\local\board_query;

/**
 * Custom completion rule of the task board: a number of the user's cards lie in done columns.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Returns the completion state of a rule for the user.
     *
     * @param string $rule The completion rule.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);
        $required = (int)($this->cm->customdata['customcompletionrules']['completioncards'] ?? 0);
        $done = board_query::count_done_cards((int)$this->cm->instance, (int)$this->userid);
        return ($required > 0 && $done >= $required) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns the custom rules this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completioncards'];
    }

    /**
     * Returns the descriptions of the custom rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $required = (int)($this->cm->customdata['customcompletionrules']['completioncards'] ?? 0);
        return ['completioncards' => get_string('completiondetail:cards', 'mod_taskboard', $required)];
    }

    /**
     * Returns all rules in display order.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionview', 'completioncards', 'completionusegrade', 'completionpassgrade'];
    }
}
