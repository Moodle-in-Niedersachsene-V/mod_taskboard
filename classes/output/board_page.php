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

namespace mod_taskboard\output;

use core\output\help_icon;
use core\output\renderable;
use core\output\renderer_base;
use core\output\single_select;
use core\output\templatable;
use core\url;
use mod_taskboard\local\board_access;
use mod_taskboard\local\board_query;
use mod_taskboard\local\board_service;
use mod_taskboard\local\presets;
use stdClass;

/**
 * The board page: switcher, card size control and the board itself.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class board_page implements renderable, templatable {
    /** @var board_access The activity and user. */
    private board_access $access;

    /** @var stdClass The board shown. */
    private stdClass $board;

    /**
     * Creates the page.
     *
     * @param board_access $access The activity and user.
     * @param stdClass $board The board shown.
     */
    public function __construct(board_access $access, stdClass $board) {
        $this->access = $access;
        $this->board = $board;
    }

    /**
     * Returns the card size the user sees.
     *
     * @param board_access $access The activity and user.
     * @return string
     */
    public static function current_cardsize(board_access $access): string {
        $size = $access->taskboard->cardsize;
        if (!empty($access->taskboard->allowusersize)) {
            $preference = get_user_preferences('mod_taskboard_cardsize_' . $access->cm->id, null, $access->userid);
            if (in_array($preference, presets::CARDSIZES, true)) {
                $size = $preference;
            }
        }
        return in_array($size, presets::CARDSIZES, true) ? $size : 'm';
    }

    /**
     * Exports the data for the board template.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $access = $this->access;
        $service = new board_service($access);
        $query = new board_query($access);
        $cardsize = self::current_cardsize($access);

        $sizes = [];
        foreach (presets::cardsize_options() as $key => $name) {
            $sizes[] = [
                'key' => $key,
                'label' => get_string('cardsizeshort_' . $key, 'mod_taskboard'),
                'name' => $name,
                'active' => $key === $cardsize,
            ];
        }

        $boardselect = null;
        $options = $service->board_options();
        if (count($options) > 1) {
            $select = new single_select(
                new url('/mod/taskboard/view.php', ['id' => $access->cm->id]),
                'board',
                $options,
                board_service::board_key($this->board),
                null
            );
            $select->set_label(get_string('selectboard', 'mod_taskboard'));
            $boardselect = $select->export_for_template($output);
        }

        $board = $query->export_board($this->board);
        return [
            'cmid' => (int)$access->cm->id,
            'board' => $board,
            'boardname' => $this->board_name(),
            'istemplate' => !empty($this->board->istemplate),
            'cardsize' => $cardsize,
            'sizes' => $sizes,
            'canchangesize' => $access->can_change_cardsize(),
            'sizehelp' => (new help_icon('cardsize', 'mod_taskboard'))->export_for_template($output),
            'boardselect' => $boardselect,
            'hasboardselect' => $boardselect !== null,
            'needsapproval' => $access->needs_approval() && $board['canadd'],
        ];
    }

    /**
     * Returns the name of the board shown, for the heading.
     *
     * @return string
     */
    private function board_name(): string {
        global $DB;
        $board = $this->board;
        if (!empty($board->istemplate)) {
            return get_string('board_template', 'mod_taskboard');
        }
        if ((int)$board->groupid > 0) {
            $name = $DB->get_field('groups', 'name', ['id' => $board->groupid]);
            $groupname = format_string((string)$name, true, ['context' => $this->access->context]);
            return get_string('board_group', 'mod_taskboard', $groupname);
        }
        if ((int)$board->userid > 0) {
            $user = \core_user::get_user((int)$board->userid);
            return get_string('board_personal', 'mod_taskboard', $user ? fullname($user) : '');
        }
        return get_string('board_course', 'mod_taskboard');
    }
}
