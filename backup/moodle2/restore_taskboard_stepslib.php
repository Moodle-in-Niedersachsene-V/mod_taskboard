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

/**
 * Restore structure of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure of mod_taskboard.
 *
 * Runs inside Moodle's restore process, which has no HTTP request, so no sesskey applies to the
 * inserts below.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_taskboard_activity_structure_step extends restore_activity_structure_step {
    /** @var bool Whether boards owned by a user or group are restored. */
    private bool $userinfo = false;

    /**
     * Defines the paths to process.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $this->userinfo = (bool)$this->get_setting_value('userinfo');
        $paths = [
            new restore_path_element('taskboard', '/activity/taskboard'),
            new restore_path_element('taskboard_board', '/activity/taskboard/boards/board'),
            new restore_path_element('taskboard_column', '/activity/taskboard/boards/board/columns/column'),
            new restore_path_element('taskboard_card', '/activity/taskboard/boards/board/columns/column/cards/card'),
            new restore_path_element('taskboard_check', '/activity/taskboard/boards/board/columns/column/cards/card/checks/check'),
            new restore_path_element(
                'taskboard_assignee',
                '/activity/taskboard/boards/board/columns/column/cards/card/assignees/assignee'
            ),
        ];
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity record.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        if ((int)$data->grade < 0) {
            $data->grade = -(int)$this->get_mappingid('scale', abs((int)$data->grade));
        }
        // phpcs:ignore moodle.Commenting.InlineComment
        $newitemid = $DB->insert_record('taskboard', $data); // nosemgrep: moodle-fehlender-sesskey
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores a board, skipping boards of users or groups that were not restored.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard_board($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->taskboardid = $this->get_new_parentid('taskboard');
        if (empty($data->istemplate) && !$this->userinfo) {
            return;
        }
        if ((int)$data->groupid > 0) {
            $data->groupid = (int)$this->get_mappingid('group', $data->groupid);
            if (!$data->groupid) {
                return;
            }
        }
        if ((int)$data->userid > 0) {
            $data->userid = (int)$this->get_mappingid('user', $data->userid);
            if (!$data->userid) {
                return;
            }
        }
        // phpcs:ignore moodle.Commenting.InlineComment
        $newid = $DB->insert_record('taskboard_boards', $data); // nosemgrep: moodle-fehlender-sesskey
        $this->set_mapping('taskboard_board', $oldid, $newid);
    }

    /**
     * Restores a column of a restored board.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard_column($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->boardid = $this->get_new_parentid('taskboard_board');
        if (!$data->boardid) {
            return;
        }
        // phpcs:ignore moodle.Commenting.InlineComment
        $newid = $DB->insert_record('taskboard_columns', $data); // nosemgrep: moodle-fehlender-sesskey
        $this->set_mapping('taskboard_column', $oldid, $newid);
    }

    /**
     * Restores a card of a restored column.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard_card($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->columnid = $this->get_new_parentid('taskboard_column');
        if (!$data->columnid) {
            return;
        }
        $data->boardid = $DB->get_field('taskboard_columns', 'boardid', ['id' => $data->columnid]);
        $data->createdby = (int)$data->createdby > 0 ? (int)$this->get_mappingid('user', $data->createdby) : 0;
        // phpcs:ignore moodle.Commenting.InlineComment
        $newid = $DB->insert_record('taskboard_cards', $data); // nosemgrep: moodle-fehlender-sesskey
        $this->set_mapping('taskboard_card', $oldid, $newid, true);
    }

    /**
     * Restores a checklist item of a restored card.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard_check($data) {
        global $DB;
        $data = (object)$data;
        $data->cardid = $this->get_new_parentid('taskboard_card');
        if (!$data->cardid) {
            return;
        }
        if (!$this->userinfo) {
            $data->done = 0;
        }
        // phpcs:ignore moodle.Commenting.InlineComment
        $DB->insert_record('taskboard_checks', $data); // nosemgrep: moodle-fehlender-sesskey
    }

    /**
     * Restores an assignment of a restored card to a restored user.
     *
     * @param array $data The element data.
     * @return void
     */
    protected function process_taskboard_assignee($data) {
        global $DB;
        $data = (object)$data;
        $data->cardid = $this->get_new_parentid('taskboard_card');
        $data->userid = (int)$this->get_mappingid('user', $data->userid);
        if (!$data->cardid || !$data->userid) {
            return;
        }
        // phpcs:ignore moodle.Commenting.InlineComment
        $DB->insert_record('taskboard_assignees', $data); // nosemgrep: moodle-fehlender-sesskey
    }

    /**
     * Restores the files after all records.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_taskboard', 'intro', null);
        $this->add_related_files('mod_taskboard', 'cardimage', 'taskboard_card');
    }
}
