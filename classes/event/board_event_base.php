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

namespace mod_taskboard\event;

/**
 * Common parts of the events about cards and columns of a task board.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class board_event_base extends \core\event\base {
    /**
     * Returns the URL of the activity page.
     *
     * @return \core\url
     */
    public function get_url() {
        return new \core\url('/mod/taskboard/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Checks the event data before it is stored.
     *
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['boardid'])) {
            throw new \coding_exception('The \'boardid\' value must be set in other.');
        }
    }

    /**
     * Returns how the ids in other are mapped on restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return ['boardid' => ['db' => 'taskboard_boards', 'restore' => 'taskboard_board']];
    }
}
