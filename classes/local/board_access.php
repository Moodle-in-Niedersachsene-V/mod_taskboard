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

use core\context\module as context_module;
use stdClass;

/**
 * Decides who may see and change which board, column and card of one task board activity.
 *
 * Every page, form and external function resolves its activity through {@see self::from_cmid()}
 * and asks this class before reading or writing, so the rules live in one place.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class board_access {
    /** @var stdClass The activity instance record. */
    public readonly stdClass $taskboard;

    /** @var \cm_info The course module. */
    public readonly \cm_info $cm;

    /** @var context_module The module context. */
    public readonly context_module $context;

    /** @var int The user the decisions are made for. */
    public readonly int $userid;

    /** @var bool|null Cached result of the manage capability check. */
    private ?bool $manage = null;

    /**
     * Creates the access object.
     *
     * @param stdClass $taskboard The activity instance record.
     * @param \cm_info $cm The course module.
     * @param context_module $context The module context.
     * @param int $userid The user the decisions are made for.
     */
    public function __construct(stdClass $taskboard, \cm_info $cm, context_module $context, int $userid) {
        $this->taskboard = $taskboard;
        $this->cm = $cm;
        $this->context = $context;
        $this->userid = $userid;
    }

    /**
     * Loads the activity for a course module id and the current user.
     *
     * Does not check any capability; callers check view access with {@see self::require_view()}.
     *
     * @param int $cmid The course module id.
     * @return self
     */
    public static function from_cmid(int $cmid): self {
        global $DB, $USER;
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'taskboard');
        $taskboard = $DB->get_record('taskboard', ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($taskboard, $cm, context_module::instance($cm->id), (int)$USER->id);
    }

    /**
     * Throws unless the user may view the activity.
     *
     * @return void
     */
    public function require_view(): void {
        require_capability('mod/taskboard:view', $this->context, $this->userid);
    }

    /**
     * Whether the user manages the activity (teachers): template, all boards, approval.
     *
     * @return bool
     */
    public function can_manage(): bool {
        if ($this->manage === null) {
            $this->manage = has_capability('mod/taskboard:manage', $this->context, $this->userid);
        }
        return $this->manage;
    }

    /**
     * Whether the user may take part on boards at all.
     *
     * @return bool
     */
    public function can_contribute(): bool {
        return has_capability('mod/taskboard:contribute', $this->context, $this->userid);
    }

    /**
     * Whether the user may open a board.
     *
     * @param stdClass $board The board record.
     * @return bool
     */
    public function can_view_board(stdClass $board): bool {
        if ((int)$board->taskboardid !== (int)$this->taskboard->id) {
            return false;
        }
        if (!has_capability('mod/taskboard:view', $this->context, $this->userid)) {
            return false;
        }
        if ($this->can_manage()) {
            return true;
        }
        if (!empty($board->istemplate)) {
            return false;
        }
        switch ((int)$this->taskboard->boardtype) {
            case presets::BOARDTYPE_GROUP:
                return (int)$board->groupid > 0 && groups_is_member((int)$board->groupid, $this->userid);
            case presets::BOARDTYPE_PERSONAL:
                return (int)$board->userid === $this->userid;
            default:
                return (int)$board->groupid === 0 && (int)$board->userid === 0;
        }
    }

    /**
     * Throws unless the user may open the board.
     *
     * @param stdClass $board The board record.
     * @return void
     */
    public function require_view_board(stdClass $board): void {
        if (!$this->can_view_board($board)) {
            throw new \moodle_exception('nopermissionboard', 'mod_taskboard');
        }
    }

    /**
     * Whether students on this activity may add cards.
     *
     * @return bool
     */
    private function students_may_add(): bool {
        return in_array($this->taskboard->studentrights, [presets::RIGHTS_OWN, presets::RIGHTS_ALL], true);
    }

    /**
     * Whether the user may add cards to the board.
     *
     * @param stdClass $board The board record.
     * @return bool
     */
    public function can_add_card(stdClass $board): bool {
        if (!$this->can_view_board($board)) {
            return false;
        }
        if ($this->can_manage()) {
            return true;
        }
        return empty($board->istemplate) && $this->can_contribute() && $this->students_may_add();
    }

    /**
     * Whether the user may see a card (cards waiting for approval stay with their author).
     *
     * @param stdClass $card The card record.
     * @return bool
     */
    public function can_see_card(stdClass $card): bool {
        return !empty($card->approved) || $this->can_manage() || (int)$card->createdby === $this->userid;
    }

    /**
     * Whether the user may edit or delete a card.
     *
     * @param stdClass $board The board the card is on.
     * @param stdClass $card The card record.
     * @return bool
     */
    public function can_edit_card(stdClass $board, stdClass $card): bool {
        if (!$this->can_view_board($board) || (int)$card->boardid !== (int)$board->id) {
            return false;
        }
        if ($this->can_manage()) {
            return true;
        }
        if (!empty($board->istemplate) || !$this->can_contribute()) {
            return false;
        }
        if ($this->taskboard->studentrights === presets::RIGHTS_ALL) {
            return $this->can_see_card($card);
        }
        return $this->taskboard->studentrights === presets::RIGHTS_OWN && (int)$card->createdby === $this->userid;
    }

    /**
     * Whether the user may move a card or tick its checklist.
     *
     * @param stdClass $board The board the card is on.
     * @param stdClass $card The card record.
     * @return bool
     */
    public function can_move_card(stdClass $board, stdClass $card): bool {
        if (!$this->can_view_board($board) || (int)$card->boardid !== (int)$board->id) {
            return false;
        }
        if ($this->can_manage()) {
            return true;
        }
        return empty($board->istemplate) && $this->can_contribute() && $this->can_see_card($card);
    }

    /**
     * Whether the user may add, rename, reorder and delete columns of the board.
     *
     * @param stdClass $board The board record.
     * @return bool
     */
    public function can_manage_columns(stdClass $board): bool {
        if (!$this->can_view_board($board)) {
            return false;
        }
        if ($this->can_manage()) {
            return true;
        }
        return empty($board->istemplate) && $this->can_contribute()
            && $this->taskboard->studentrights === presets::RIGHTS_ALL;
    }

    /**
     * Whether new cards by this user need approval before others see them.
     *
     * @return bool
     */
    public function needs_approval(): bool {
        return !empty($this->taskboard->approval) && !$this->can_manage();
    }

    /**
     * Whether the user may switch the card size for themselves.
     *
     * @return bool
     */
    public function can_change_cardsize(): bool {
        return !empty($this->taskboard->allowusersize) || $this->can_manage();
    }

    /**
     * Loads a board of this activity and checks the user may view it.
     *
     * @param int $boardid The board id.
     * @return stdClass The board record.
     */
    public function get_board(int $boardid): stdClass {
        global $DB;
        $board = $DB->get_record('taskboard_boards', ['id' => $boardid, 'taskboardid' => $this->taskboard->id], '*', MUST_EXIST);
        $this->require_view_board($board);
        return $board;
    }

    /**
     * Loads a card of this activity together with its board, checking the user may see both.
     *
     * @param int $cardid The card id.
     * @return array{0: stdClass, 1: stdClass} The board and the card.
     */
    public function get_card(int $cardid): array {
        global $DB;
        $card = $DB->get_record('taskboard_cards', ['id' => $cardid], '*', MUST_EXIST);
        $board = $this->get_board((int)$card->boardid);
        if (!$this->can_see_card($card)) {
            throw new \moodle_exception('nopermissioncard', 'mod_taskboard');
        }
        return [$board, $card];
    }

    /**
     * Loads a column of this activity together with its board, checking the user may view the board.
     *
     * @param int $columnid The column id.
     * @return array{0: stdClass, 1: stdClass} The board and the column.
     */
    public function get_column(int $columnid): array {
        global $DB;
        $column = $DB->get_record('taskboard_columns', ['id' => $columnid], '*', MUST_EXIST);
        $board = $this->get_board((int)$column->boardid);
        return [$board, $column];
    }
}
