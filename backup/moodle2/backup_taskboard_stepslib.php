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
 * Backup structure of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure of mod_taskboard.
 *
 * The template board with the cards teachers prepared is always included; the boards of the
 * course, groups and users only with user data.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_taskboard_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure of the backup.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $taskboard = new backup_nested_element('taskboard', ['id'], [
            'name', 'intro', 'introformat', 'boardtype', 'columntemplate', 'cardsize', 'allowusersize',
            'wiplimit', 'studentrights', 'approval', 'allowimages', 'requirealt', 'allowvideos',
            'completioncards', 'grade', 'timecreated', 'timemodified',
        ]);
        $boards = new backup_nested_element('boards');
        $board = new backup_nested_element('board', ['id'], [
            'groupid', 'userid', 'istemplate', 'timecreated', 'timemodified',
        ]);
        $columns = new backup_nested_element('columns');
        $column = new backup_nested_element('column', ['id'], [
            'title', 'sortorder', 'wiplimit', 'isdone', 'timecreated', 'timemodified',
        ]);
        $cards = new backup_nested_element('cards');
        $card = new backup_nested_element('card', ['id'], [
            'sortorder', 'title', 'description', 'labels', 'duedate', 'videourl', 'imagealt',
            'approved', 'createdby', 'timecreated', 'timemodified',
        ]);
        $checks = new backup_nested_element('checks');
        $check = new backup_nested_element('check', ['id'], ['text', 'done', 'sortorder']);
        $assignees = new backup_nested_element('assignees');
        $assignee = new backup_nested_element('assignee', ['id'], ['userid']);

        $taskboard->add_child($boards);
        $boards->add_child($board);
        $board->add_child($columns);
        $columns->add_child($column);
        $column->add_child($cards);
        $cards->add_child($card);
        $card->add_child($checks);
        $checks->add_child($check);
        $card->add_child($assignees);
        $assignees->add_child($assignee);

        $taskboard->set_source_table('taskboard', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $board->set_source_table('taskboard_boards', ['taskboardid' => backup::VAR_PARENTID]);
        } else {
            $board->set_source_sql(
                'SELECT * FROM {taskboard_boards} WHERE taskboardid = ? AND istemplate = 1',
                [backup::VAR_PARENTID]
            );
        }
        $column->set_source_table('taskboard_columns', ['boardid' => backup::VAR_PARENTID], 'sortorder ASC');
        $card->set_source_table('taskboard_cards', ['columnid' => backup::VAR_PARENTID], 'sortorder ASC');
        $check->set_source_table('taskboard_checks', ['cardid' => backup::VAR_PARENTID], 'sortorder ASC');
        if ($userinfo) {
            $assignee->set_source_table('taskboard_assignees', ['cardid' => backup::VAR_PARENTID]);
        }

        $board->annotate_ids('group', 'groupid');
        $board->annotate_ids('user', 'userid');
        $card->annotate_ids('user', 'createdby');
        $assignee->annotate_ids('user', 'userid');

        $taskboard->annotate_files('mod_taskboard', 'intro', null);
        $card->annotate_files('mod_taskboard', 'cardimage', 'id');

        return $this->prepare_activity_structure($taskboard);
    }
}
