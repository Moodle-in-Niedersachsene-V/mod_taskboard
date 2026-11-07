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

namespace mod_taskboard\local;

use mod_taskboard\event\card_approved;
use mod_taskboard\event\card_created;
use mod_taskboard\event\card_deleted;
use mod_taskboard\event\card_moved;
use mod_taskboard\event\card_updated;
use mod_taskboard\event\column_created;
use mod_taskboard\event\column_deleted;
use mod_taskboard\event\column_updated;
use stdClass;

/**
 * Changes boards, columns and cards of one task board activity.
 *
 * Callers check permissions with {@see board_access} first; this class keeps positions,
 * events, completion and grades consistent.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class board_service {
    /** @var board_access The activity and user the changes are made for. */
    private board_access $access;

    /**
     * Creates the service.
     *
     * @param board_access $access The activity and user the changes are made for.
     */
    public function __construct(board_access $access) {
        $this->access = $access;
    }

    /**
     * Returns the board for an owner, creating it from the template board when missing.
     *
     * @param int $groupid The group of a group board, else 0.
     * @param int $userid The user of a personal board, else 0.
     * @param bool $istemplate Whether the template board is wanted.
     * @return stdClass The board record.
     */
    public function get_or_create_board(int $groupid, int $userid, bool $istemplate): stdClass {
        global $DB;
        $conditions = [
            'taskboardid' => $this->access->taskboard->id,
            'istemplate' => $istemplate ? 1 : 0,
            'groupid' => $istemplate ? 0 : $groupid,
            'userid' => $istemplate ? 0 : $userid,
        ];
        $board = $DB->get_record('taskboard_boards', $conditions);
        if ($board) {
            return $board;
        }
        $now = time();
        $board = (object)($conditions + ['timecreated' => $now, 'timemodified' => $now]);
        $board->id = $DB->insert_record('taskboard_boards', $board);
        if ($istemplate) {
            $this->create_template_columns($board);
        } else {
            $this->copy_template($board);
        }
        return $board;
    }

    /**
     * Picks the board the current user opens, honouring a selection from the board switcher.
     *
     * Selection keys: 't' for the template, 'g<id>' for a group, 'u<id>' for a user.
     *
     * @param string $selection The requested board key, empty for the default.
     * @return stdClass The board record.
     */
    public function resolve_board(string $selection): stdClass {
        $access = $this->access;
        $manage = $access->can_manage();
        if ($selection === 't' && $manage) {
            return $this->get_or_create_board(0, 0, true);
        }
        switch ((int)$access->taskboard->boardtype) {
            case presets::BOARDTYPE_GROUP:
                $groups = $this->selectable_groups();
                if (preg_match('/^g(\d+)$/', $selection, $match) && isset($groups[(int)$match[1]])) {
                    return $this->get_or_create_board((int)$match[1], 0, false);
                }
                if ($groups) {
                    return $this->get_or_create_board((int)array_key_first($groups), 0, false);
                }
                if ($manage) {
                    return $this->get_or_create_board(0, 0, true);
                }
                throw new \moodle_exception('nogroup', 'mod_taskboard');
            case presets::BOARDTYPE_PERSONAL:
                if ($manage && preg_match('/^u(\d+)$/', $selection, $match)) {
                    $users = $this->selectable_users();
                    if (isset($users[(int)$match[1]])) {
                        return $this->get_or_create_board(0, (int)$match[1], false);
                    }
                }
                return $this->get_or_create_board(0, $access->userid, false);
            default:
                return $this->get_or_create_board(0, 0, false);
        }
    }

    /**
     * Returns the boards the current user can switch between, as selection key => name.
     *
     * @return array<string, string>
     */
    public function board_options(): array {
        $access = $this->access;
        $options = [];
        switch ((int)$access->taskboard->boardtype) {
            case presets::BOARDTYPE_GROUP:
                foreach ($this->selectable_groups() as $group) {
                    $options['g' . $group->id] = format_string($group->name, true, ['context' => $access->context]);
                }
                break;
            case presets::BOARDTYPE_PERSONAL:
                if ($access->can_manage()) {
                    foreach ($this->selectable_users() as $user) {
                        $options['u' . $user->id] = fullname($user);
                    }
                }
                break;
            default:
                $options['c'] = get_string('board_course', 'mod_taskboard');
        }
        if ($access->can_manage()) {
            $options = ['t' => get_string('board_template', 'mod_taskboard')] + $options;
        }
        return $options;
    }

    /**
     * Returns the selection key of a board, as used by {@see self::board_options()}.
     *
     * @param stdClass $board The board record.
     * @return string
     */
    public static function board_key(stdClass $board): string {
        if (!empty($board->istemplate)) {
            return 't';
        }
        if ((int)$board->groupid > 0) {
            return 'g' . $board->groupid;
        }
        if ((int)$board->userid > 0) {
            return 'u' . $board->userid;
        }
        return 'c';
    }

    /**
     * Returns the groups whose boards the current user may open, keyed by id.
     *
     * @return array<int, stdClass>
     */
    private function selectable_groups(): array {
        $access = $this->access;
        $userid = $access->can_manage() ? 0 : $access->userid;
        return groups_get_all_groups($access->cm->course, $userid, $access->cm->groupingid, 'g.id, g.name');
    }

    /**
     * Returns the users with a personal board a manager may open, keyed by id.
     *
     * @return array<int, stdClass>
     */
    private function selectable_users(): array {
        $fields = 'u.id, ' . implode(', ', array_map(
            fn($field) => 'u.' . $field,
            \core_user\fields::get_name_fields()
        ));
        return get_enrolled_users($this->access->context, 'mod/taskboard:contribute', 0, $fields, 'u.lastname, u.firstname');
    }

    /**
     * Returns the users that can be assigned to cards on a board, keyed by id.
     *
     * @param stdClass $board The board record.
     * @return array<int, stdClass>
     */
    public function board_members(stdClass $board): array {
        global $DB;
        $namefields = \core_user\fields::get_name_fields();
        if ((int)$board->userid > 0) {
            $fields = 'id, ' . implode(', ', $namefields);
            $user = $DB->get_record('user', ['id' => $board->userid, 'deleted' => 0], $fields);
            return $user ? [(int)$user->id => $user] : [];
        }
        if (!empty($board->istemplate)) {
            return [];
        }
        $fields = 'u.id, ' . implode(', ', array_map(fn($field) => 'u.' . $field, $namefields));
        return get_enrolled_users(
            $this->access->context,
            'mod/taskboard:contribute',
            (int)$board->groupid,
            $fields,
            'u.lastname, u.firstname'
        );
    }

    /**
     * Adds a card at the end of a column.
     *
     * @param stdClass $board The board record.
     * @param int $columnid The column the card goes into.
     * @param card_data $data The card content.
     * @return int The new card id.
     */
    public function add_card(stdClass $board, int $columnid, card_data $data): int {
        global $DB;
        $this->require_column_on_board($board, $columnid);
        $now = time();
        $card = (object)[
            'boardid' => $board->id,
            'columnid' => $columnid,
            'sortorder' => $this->next_card_position($columnid),
            'createdby' => $this->access->userid,
            'approved' => $this->access->needs_approval() ? 0 : 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $this->apply_card_data($card, $data);
        $card->id = $DB->insert_record('taskboard_cards', $card);
        $this->save_assignees((int)$card->id, $data->assignees);
        $this->save_checks((int)$card->id, $data->checks);
        $this->save_links((int)$card->id, $data->links);
        $this->touch_board((int)$board->id);
        card_created::create_for_card($this->access->context, $card)->trigger();
        $this->refresh_progress($card);
        return (int)$card->id;
    }

    /**
     * Replaces the content of a card.
     *
     * @param stdClass $card The card record.
     * @param card_data $data The new content.
     * @return void
     */
    public function update_card(stdClass $card, card_data $data): void {
        global $DB;
        $this->apply_card_data($card, $data);
        $card->timemodified = time();
        $DB->update_record('taskboard_cards', $card);
        $this->save_assignees((int)$card->id, $data->assignees);
        $this->save_checks((int)$card->id, $data->checks);
        $this->save_links((int)$card->id, $data->links);
        $this->touch_board((int)$card->boardid);
        card_updated::create_for_card($this->access->context, $card)->trigger();
        $this->refresh_progress($card);
    }

    /**
     * Moves a card to a position in a column of the same board.
     *
     * @param stdClass $card The card record.
     * @param int $columnid The target column.
     * @param int $position The target position, 0 for the top; larger values put the card last.
     * @return void
     */
    public function move_card(stdClass $card, int $columnid, int $position): void {
        global $DB;
        $board = $DB->get_record('taskboard_boards', ['id' => $card->boardid], '*', MUST_EXIST);
        $this->require_column_on_board($board, $columnid);
        $fromcolumnid = (int)$card->columnid;

        $transaction = $DB->start_delegated_transaction();
        $ids = $DB->get_fieldset_sql(
            'SELECT id FROM {taskboard_cards} WHERE columnid = :columnid AND id <> :cardid ORDER BY sortorder, id',
            ['columnid' => $columnid, 'cardid' => $card->id]
        );
        $position = max(0, min($position, count($ids)));
        array_splice($ids, $position, 0, [(int)$card->id]);
        $card->columnid = $columnid;
        $card->timemodified = time();
        $DB->update_record('taskboard_cards', $card);
        $this->write_positions($ids);
        if ($fromcolumnid !== $columnid) {
            $this->renumber_column($fromcolumnid);
        }
        $this->touch_board((int)$board->id);
        $transaction->allow_commit();

        card_moved::create_for_move($this->access->context, $card, $fromcolumnid, $position)->trigger();
        if ($fromcolumnid !== $columnid) {
            $this->refresh_progress($card);
        }
    }

    /**
     * Deletes a card with its checklist, assignees and image.
     *
     * @param stdClass $card The card record.
     * @return void
     */
    public function delete_card(stdClass $card): void {
        $users = $this->card_users($card);
        $this->delete_card_rows($card);
        $this->renumber_column((int)$card->columnid);
        $this->touch_board((int)$card->boardid);
        card_deleted::create_for_card($this->access->context, $card)->trigger();
        $this->refresh_progress_for_users($users);
    }

    /**
     * Ticks or unticks a checklist item.
     *
     * @param stdClass $card The card the item belongs to.
     * @param int $checkid The checklist item id.
     * @param bool $done Whether the item is done.
     * @return void
     */
    public function set_check(stdClass $card, int $checkid, bool $done): void {
        global $DB;
        $check = $DB->get_record('taskboard_checks', ['id' => $checkid, 'cardid' => $card->id], '*', MUST_EXIST);
        $DB->set_field('taskboard_checks', 'done', $done ? 1 : 0, ['id' => $check->id]);
        $card->timemodified = time();
        $DB->set_field('taskboard_cards', 'timemodified', $card->timemodified, ['id' => $card->id]);
        $this->touch_board((int)$card->boardid);
        card_updated::create_for_card($this->access->context, $card)->trigger();
    }

    /**
     * Makes a card that waited for approval visible to everyone on the board.
     *
     * @param stdClass $card The card record.
     * @return void
     */
    public function approve_card(stdClass $card): void {
        global $DB;
        $card->approved = 1;
        $card->timemodified = time();
        $DB->update_record('taskboard_cards', $card);
        $this->touch_board((int)$card->boardid);
        card_approved::create_for_card($this->access->context, $card)->trigger();
        $this->refresh_progress($card);
    }

    /**
     * Records that the image of a card changed.
     *
     * @param stdClass $card The card record.
     * @param string $imagealt The alternative text of the image.
     * @return void
     */
    public function image_changed(stdClass $card, string $imagealt): void {
        global $DB;
        $card->imagealt = \core_text::substr(trim($imagealt), 0, 255);
        $card->timemodified = time();
        $DB->update_record('taskboard_cards', $card);
        $this->touch_board((int)$card->boardid);
        card_updated::create_for_card($this->access->context, $card)->trigger();
    }

    /**
     * Adds a column at the right end of a board.
     *
     * @param stdClass $board The board record.
     * @param string $title The column title.
     * @param int $wiplimit Work-in-progress limit, 0 for none.
     * @param bool $isdone Whether cards in this column count as done.
     * @return int The new column id.
     */
    public function add_column(stdClass $board, string $title, int $wiplimit, bool $isdone): int {
        global $DB;
        $max = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {taskboard_columns} WHERE boardid = :boardid',
            ['boardid' => $board->id]
        );
        $now = time();
        $column = (object)[
            'boardid' => $board->id,
            'title' => \core_text::substr(trim($title), 0, 255),
            'sortorder' => $max === null ? 0 : (int)$max + 1,
            'wiplimit' => max(0, $wiplimit),
            'isdone' => $isdone ? 1 : 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $column->id = $DB->insert_record('taskboard_columns', $column);
        $this->touch_board((int)$board->id);
        column_created::create_for_column($this->access->context, $column)->trigger();
        return (int)$column->id;
    }

    /**
     * Changes title, limit and done flag of a column.
     *
     * @param stdClass $column The column record.
     * @param string $title The column title.
     * @param int $wiplimit Work-in-progress limit, 0 for none.
     * @param bool $isdone Whether cards in this column count as done.
     * @return void
     */
    public function update_column(stdClass $column, string $title, int $wiplimit, bool $isdone): void {
        global $DB;
        $donechanged = (bool)$column->isdone !== $isdone;
        $column->title = \core_text::substr(trim($title), 0, 255);
        $column->wiplimit = max(0, $wiplimit);
        $column->isdone = $isdone ? 1 : 0;
        $column->timemodified = time();
        $DB->update_record('taskboard_columns', $column);
        $this->touch_board((int)$column->boardid);
        column_updated::create_for_column($this->access->context, $column)->trigger();
        if ($donechanged) {
            $cards = $DB->get_records('taskboard_cards', ['columnid' => $column->id]);
            $users = [];
            foreach ($cards as $card) {
                $users = array_merge($users, $this->card_users($card));
            }
            $this->refresh_progress_for_users(array_unique($users));
        }
    }

    /**
     * Swaps a column with its left or right neighbour.
     *
     * @param stdClass $column The column record.
     * @param int $direction -1 to move left, 1 to move right.
     * @return void
     */
    public function move_column(stdClass $column, int $direction): void {
        global $DB;
        $ids = $DB->get_fieldset_sql(
            'SELECT id FROM {taskboard_columns} WHERE boardid = :boardid ORDER BY sortorder, id',
            ['boardid' => $column->boardid]
        );
        $index = array_search((int)$column->id, array_map('intval', $ids), true);
        $target = $index + ($direction < 0 ? -1 : 1);
        if ($index === false || $target < 0 || $target >= count($ids)) {
            throw new \moodle_exception('cannotmovecolumn', 'mod_taskboard');
        }
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        foreach (array_values($ids) as $position => $id) {
            $DB->set_field('taskboard_columns', 'sortorder', $position, ['id' => $id]);
        }
        $this->touch_board((int)$column->boardid);
        column_updated::create_for_column($this->access->context, $column)->trigger();
    }

    /**
     * Deletes a column together with all its cards.
     *
     * @param stdClass $column The column record.
     * @return void
     */
    public function delete_column(stdClass $column): void {
        global $DB;
        $users = [];
        foreach ($DB->get_records('taskboard_cards', ['columnid' => $column->id]) as $card) {
            $users = array_merge($users, $this->card_users($card));
            $this->delete_card_rows($card);
        }
        $DB->delete_records('taskboard_columns', ['id' => $column->id]);
        $ids = $DB->get_fieldset_sql(
            'SELECT id FROM {taskboard_columns} WHERE boardid = :boardid ORDER BY sortorder, id',
            ['boardid' => $column->boardid]
        );
        foreach (array_values($ids) as $position => $id) {
            $DB->set_field('taskboard_columns', 'sortorder', $position, ['id' => $id]);
        }
        $this->touch_board((int)$column->boardid);
        column_deleted::create_for_column($this->access->context, $column)->trigger();
        $this->refresh_progress_for_users(array_unique($users));
    }

    /**
     * Deletes all boards of an activity with their columns, cards and files.
     *
     * Used when the activity is deleted and by the privacy provider; raises no events.
     *
     * @param int $taskboardid The activity instance id.
     * @param \context $context The module context.
     * @return void
     */
    public static function delete_all_boards(int $taskboardid, \context $context): void {
        global $DB;
        $boardids = $DB->get_fieldset_select('taskboard_boards', 'id', 'taskboardid = :id', ['id' => $taskboardid]);
        foreach ($boardids as $boardid) {
            self::delete_board_rows((int)$boardid);
        }
        get_file_storage()->delete_area_files($context->id, 'mod_taskboard', 'cardimage');
    }

    /**
     * Deletes one board with its columns, cards, checklists and assignees, but not its files.
     *
     * @param int $boardid The board id.
     * @return void
     */
    public static function delete_board_rows(int $boardid): void {
        global $DB;
        $cardids = $DB->get_fieldset_select('taskboard_cards', 'id', 'boardid = :boardid', ['boardid' => $boardid]);
        if ($cardids) {
            [$insql, $params] = $DB->get_in_or_equal($cardids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('taskboard_checks', "cardid $insql", $params);
            $DB->delete_records_select('taskboard_assignees', "cardid $insql", $params);
            $DB->delete_records_select('taskboard_links', "cardid $insql", $params);
        }
        $DB->delete_records('taskboard_cards', ['boardid' => $boardid]);
        $DB->delete_records('taskboard_columns', ['boardid' => $boardid]);
        $DB->delete_records('taskboard_boards', ['id' => $boardid]);
    }

    /**
     * Throws unless the column belongs to the board.
     *
     * @param stdClass $board The board record.
     * @param int $columnid The column id.
     * @return void
     */
    private function require_column_on_board(stdClass $board, int $columnid): void {
        global $DB;
        if (!$DB->record_exists('taskboard_columns', ['id' => $columnid, 'boardid' => $board->id])) {
            throw new \moodle_exception('invalidcolumn', 'mod_taskboard');
        }
    }

    /**
     * Copies the card content fields onto a card record.
     *
     * @param stdClass $card The card record to fill.
     * @param card_data $data The card content.
     * @return void
     */
    private function apply_card_data(stdClass $card, card_data $data): void {
        $card->title = \core_text::substr(trim($data->title), 0, 255);
        $card->description = $data->description;
        $card->labels = implode(',', presets::clean_labels($data->labels));
        $card->duedate = max(0, $data->duedate);
        $card->videourl = trim($data->videourl);
        $card->imagealt = \core_text::substr(trim($data->imagealt), 0, 255);
    }

    /**
     * Replaces the assignees of a card.
     *
     * @param int $cardid The card id.
     * @param int[] $userids The assigned user ids.
     * @return void
     */
    private function save_assignees(int $cardid, array $userids): void {
        global $DB;
        $DB->delete_records('taskboard_assignees', ['cardid' => $cardid]);
        foreach (array_unique(array_map('intval', $userids)) as $userid) {
            if ($userid > 0) {
                $DB->insert_record('taskboard_assignees', (object)['cardid' => $cardid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Replaces the checklist of a card, keeping the done state of items whose text is unchanged.
     *
     * @param int $cardid The card id.
     * @param string[] $texts The item texts in order.
     * @return void
     */
    private function save_checks(int $cardid, array $texts): void {
        global $DB;
        $done = [];
        foreach ($DB->get_records('taskboard_checks', ['cardid' => $cardid]) as $check) {
            if ($check->done) {
                $done[$check->text] = true;
            }
        }
        $DB->delete_records('taskboard_checks', ['cardid' => $cardid]);
        foreach (array_values($texts) as $position => $text) {
            $DB->insert_record('taskboard_checks', (object)[
                'cardid' => $cardid,
                'text' => $text,
                'done' => isset($done[$text]) ? 1 : 0,
                'sortorder' => $position,
            ]);
        }
    }

    /**
     * Replaces the links of a card.
     *
     * @param int $cardid The card id.
     * @param array[] $links Links in order, each with url and title.
     * @return void
     */
    private function save_links(int $cardid, array $links): void {
        global $DB;
        $DB->delete_records('taskboard_links', ['cardid' => $cardid]);
        foreach (array_values(array_slice($links, 0, card_data::MAX_LINKS)) as $position => $link) {
            $DB->insert_record('taskboard_links', (object)[
                'cardid' => $cardid,
                'url' => $link['url'],
                'title' => $link['title'] !== '' ? $link['title'] : null,
                'sortorder' => $position,
            ]);
        }
    }

    /**
     * Deletes the rows and image of one card without renumbering or events.
     *
     * @param stdClass $card The card record.
     * @return void
     */
    private function delete_card_rows(stdClass $card): void {
        global $DB;
        $DB->delete_records('taskboard_checks', ['cardid' => $card->id]);
        $DB->delete_records('taskboard_assignees', ['cardid' => $card->id]);
        $DB->delete_records('taskboard_links', ['cardid' => $card->id]);
        $DB->delete_records('taskboard_cards', ['id' => $card->id]);
        get_file_storage()->delete_area_files($this->access->context->id, 'mod_taskboard', 'cardimage', $card->id);
    }

    /**
     * Returns the position after the last card of a column.
     *
     * @param int $columnid The column id.
     * @return int
     */
    private function next_card_position(int $columnid): int {
        global $DB;
        $max = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {taskboard_cards} WHERE columnid = :columnid',
            ['columnid' => $columnid]
        );
        return $max === null ? 0 : (int)$max + 1;
    }

    /**
     * Gives the cards of a column consecutive positions.
     *
     * @param int $columnid The column id.
     * @return void
     */
    private function renumber_column(int $columnid): void {
        global $DB;
        $ids = $DB->get_fieldset_sql(
            'SELECT id FROM {taskboard_cards} WHERE columnid = :columnid ORDER BY sortorder, id',
            ['columnid' => $columnid]
        );
        $this->write_positions($ids);
    }

    /**
     * Stores the given order of card ids as their positions.
     *
     * @param array $ids Card ids in their new order.
     * @return void
     */
    private function write_positions(array $ids): void {
        global $DB;
        foreach (array_values($ids) as $position => $id) {
            $DB->set_field('taskboard_cards', 'sortorder', $position, ['id' => $id]);
        }
    }

    /**
     * Marks a board as changed, so open pages fetch the new state.
     *
     * @param int $boardid The board id.
     * @return void
     */
    private function touch_board(int $boardid): void {
        global $DB;
        $DB->set_field('taskboard_boards', 'timemodified', time(), ['id' => $boardid]);
    }

    /**
     * Creates the columns of a new template board from the chosen column template.
     *
     * @param stdClass $board The template board record.
     * @return void
     */
    private function create_template_columns(stdClass $board): void {
        global $DB;
        $templates = presets::column_templates();
        $key = $this->access->taskboard->columntemplate;
        $columns = $templates[$key] ?? $templates['classic'];
        $now = time();
        foreach ($columns as $position => $definition) {
            $DB->insert_record('taskboard_columns', (object)[
                'boardid' => $board->id,
                'title' => get_string($definition['title'], 'mod_taskboard'),
                'sortorder' => $position,
                'wiplimit' => $definition['wip'] ? (int)$this->access->taskboard->wiplimit : 0,
                'isdone' => $definition['done'] ? 1 : 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }

    /**
     * Fills a new board with a copy of the template board's columns and approved cards.
     *
     * @param stdClass $board The new board record.
     * @return void
     */
    private function copy_template(stdClass $board): void {
        global $DB;
        $template = $this->get_or_create_board(0, 0, true);
        $fs = get_file_storage();
        $contextid = $this->access->context->id;
        $now = time();
        $columns = $DB->get_records('taskboard_columns', ['boardid' => $template->id], 'sortorder, id');
        foreach ($columns as $column) {
            $oldcolumnid = $column->id;
            unset($column->id);
            $column->boardid = $board->id;
            $column->timecreated = $now;
            $column->timemodified = $now;
            $newcolumnid = $DB->insert_record('taskboard_columns', $column);
            $cards = $DB->get_records('taskboard_cards', ['columnid' => $oldcolumnid, 'approved' => 1], 'sortorder, id');
            foreach ($cards as $card) {
                $oldcardid = $card->id;
                unset($card->id);
                $card->boardid = $board->id;
                $card->columnid = $newcolumnid;
                $card->timecreated = $now;
                $card->timemodified = $now;
                $newcardid = $DB->insert_record('taskboard_cards', $card);
                foreach ($DB->get_records('taskboard_checks', ['cardid' => $oldcardid], 'sortorder') as $check) {
                    unset($check->id);
                    $check->cardid = $newcardid;
                    $check->done = 0;
                    $DB->insert_record('taskboard_checks', $check);
                }
                foreach ($DB->get_records('taskboard_links', ['cardid' => $oldcardid], 'sortorder') as $link) {
                    unset($link->id);
                    $link->cardid = $newcardid;
                    $DB->insert_record('taskboard_links', $link);
                }
                foreach ($fs->get_area_files($contextid, 'mod_taskboard', 'cardimage', $oldcardid, 'id', false) as $file) {
                    $fs->create_file_from_storedfile(['itemid' => $newcardid], $file);
                }
            }
        }
    }

    /**
     * Returns the users whose progress depends on a card: its author and assignees.
     *
     * @param stdClass $card The card record.
     * @return int[]
     */
    private function card_users(stdClass $card): array {
        global $DB;
        $users = array_map('intval', $DB->get_fieldset_select(
            'taskboard_assignees',
            'userid',
            'cardid = :cardid',
            ['cardid' => $card->id]
        ));
        if ((int)$card->createdby > 0) {
            $users[] = (int)$card->createdby;
        }
        return array_values(array_unique($users));
    }

    /**
     * Updates completion and grade of the users a card counts for.
     *
     * @param stdClass $card The card record.
     * @return void
     */
    private function refresh_progress(stdClass $card): void {
        $this->refresh_progress_for_users($this->card_users($card));
    }

    /**
     * Updates completion and grade for users after their done cards may have changed.
     *
     * @param int[] $userids The user ids.
     * @return void
     */
    private function refresh_progress_for_users(array $userids): void {
        global $CFG;
        $taskboard = $this->access->taskboard;
        if (empty($taskboard->completioncards) || !$userids) {
            return;
        }
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/mod/taskboard/lib.php');
        $course = get_course($this->access->cm->course);
        $completion = new \completion_info($course);
        $cm = $this->access->cm;
        foreach ($userids as $userid) {
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
            if ((int)$taskboard->grade > 0) {
                taskboard_update_grades($taskboard, $userid);
            }
        }
    }
}
