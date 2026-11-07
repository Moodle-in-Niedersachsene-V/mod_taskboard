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

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Return structures shared by the external functions of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class structures {
    /**
     * Structure of a list of card labels.
     *
     * @return external_multiple_structure
     */
    private static function labels(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'key' => new external_value(PARAM_ALPHA, 'Label key'),
            'name' => new external_value(PARAM_TEXT, 'Label name'),
        ]));
    }

    /**
     * Structure of a list of assigned users.
     *
     * @return external_multiple_structure
     */
    private static function assignees(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            'initials' => new external_value(PARAM_TEXT, 'Initials'),
            'colour' => new external_value(PARAM_INT, 'Colour number from 1 to 6'),
        ]));
    }

    /**
     * Structure of a card as shown on the board.
     *
     * @return external_single_structure
     */
    public static function card_summary(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Card id'),
            'columnid' => new external_value(PARAM_INT, 'Column id'),
            'title' => new external_value(PARAM_TEXT, 'Title'),
            'arialabel' => new external_value(PARAM_TEXT, 'Accessible summary'),
            'labels' => self::labels(),
            'haslabels' => new external_value(PARAM_BOOL, 'Whether the card has labels'),
            'hasduedate' => new external_value(PARAM_BOOL, 'Whether a due date is set'),
            'duedate' => new external_value(PARAM_TEXT, 'Formatted due date'),
            'overdue' => new external_value(PARAM_BOOL, 'Whether the due date has passed'),
            'haschecks' => new external_value(PARAM_BOOL, 'Whether the card has a checklist'),
            'checksdone' => new external_value(PARAM_INT, 'Done checklist items'),
            'checkstotal' => new external_value(PARAM_INT, 'All checklist items'),
            'hasimage' => new external_value(PARAM_BOOL, 'Whether the card has an image'),
            'imageurl' => new external_value(PARAM_URL, 'Image address'),
            'hasvideo' => new external_value(PARAM_BOOL, 'Whether the card has a video'),
            'isdone' => new external_value(PARAM_BOOL, 'Whether the card lies in a done column'),
            'pending' => new external_value(PARAM_BOOL, 'Whether the card waits for approval'),
            'assignees' => self::assignees(),
            'hasmoreassignees' => new external_value(PARAM_BOOL, 'Whether more users are assigned than shown'),
            'moreassignees' => new external_value(PARAM_INT, 'Number of assigned users not shown'),
            'canmove' => new external_value(PARAM_BOOL, 'Whether the user may move the card'),
            'canedit' => new external_value(PARAM_BOOL, 'Whether the user may edit the card'),
        ]);
    }

    /**
     * Structure of a board with its columns and cards.
     *
     * @return external_single_structure
     */
    public static function board(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Board id'),
            'key' => new external_value(PARAM_ALPHANUM, 'Board selection key'),
            'istemplate' => new external_value(PARAM_BOOL, 'Whether this is the template board'),
            'timemodified' => new external_value(PARAM_INT, 'Last change of the board'),
            'canmanagecolumns' => new external_value(PARAM_BOOL, 'Whether the user may change columns'),
            'canadd' => new external_value(PARAM_BOOL, 'Whether the user may add cards'),
            'columns' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Column id'),
                'title' => new external_value(PARAM_TEXT, 'Column title'),
                'wiplimit' => new external_value(PARAM_INT, 'Work-in-progress limit, 0 for none'),
                'haswiplimit' => new external_value(PARAM_BOOL, 'Whether a limit is set'),
                'isdone' => new external_value(PARAM_BOOL, 'Whether cards here count as done'),
                'count' => new external_value(PARAM_INT, 'Number of visible cards'),
                'overlimit' => new external_value(PARAM_BOOL, 'Whether the limit is exceeded'),
                'countlabel' => new external_value(PARAM_TEXT, 'Count as shown'),
                'canmanage' => new external_value(PARAM_BOOL, 'Whether the user may change the column'),
                'canmoveleft' => new external_value(PARAM_BOOL, 'Whether the column can move left'),
                'canmoveright' => new external_value(PARAM_BOOL, 'Whether the column can move right'),
                'canadd' => new external_value(PARAM_BOOL, 'Whether the user may add cards'),
                'cards' => new external_multiple_structure(self::card_summary()),
            ])),
        ]);
    }

    /**
     * Structure of a card in full.
     *
     * @return external_single_structure
     */
    public static function card_detail(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Card id'),
            'boardid' => new external_value(PARAM_INT, 'Board id'),
            'title' => new external_value(PARAM_TEXT, 'Title'),
            'description' => new external_value(PARAM_RAW, 'Description, formatted with format_text()'),
            'hasdescription' => new external_value(PARAM_BOOL, 'Whether a description is set'),
            'columntitle' => new external_value(PARAM_TEXT, 'Title of the column'),
            'labels' => self::labels(),
            'hasduedate' => new external_value(PARAM_BOOL, 'Whether a due date is set'),
            'duedate' => new external_value(PARAM_TEXT, 'Formatted due date'),
            'author' => new external_value(PARAM_TEXT, 'Name of the author'),
            'assignees' => self::assignees(),
            'hasassignees' => new external_value(PARAM_BOOL, 'Whether users are assigned'),
            'checks' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Checklist item id'),
                'text' => new external_value(PARAM_TEXT, 'Item text'),
                'done' => new external_value(PARAM_BOOL, 'Whether the item is done'),
            ])),
            'haschecks' => new external_value(PARAM_BOOL, 'Whether the card has a checklist'),
            'checksdone' => new external_value(PARAM_INT, 'Done checklist items'),
            'checkstotal' => new external_value(PARAM_INT, 'All checklist items'),
            'checkspercent' => new external_value(PARAM_INT, 'Done items in percent'),
            'hasimage' => new external_value(PARAM_BOOL, 'Whether the card has an image'),
            'imageurl' => new external_value(PARAM_URL, 'Image address'),
            'imagealt' => new external_value(PARAM_TEXT, 'Alternative text of the image'),
            'hasvideo' => new external_value(PARAM_BOOL, 'Whether an embeddable video is set'),
            'videourl' => new external_value(PARAM_URL, 'Embed address of the video'),
            'approved' => new external_value(PARAM_BOOL, 'Whether the card is approved'),
            'canapprove' => new external_value(PARAM_BOOL, 'Whether the user may approve the card'),
            'canedit' => new external_value(PARAM_BOOL, 'Whether the user may edit the card'),
            'canmove' => new external_value(PARAM_BOOL, 'Whether the user may move the card and tick items'),
            'canaddimage' => new external_value(PARAM_BOOL, 'Whether the user may set the image'),
        ]);
    }

    /**
     * Structure returned by functions that change a board.
     *
     * @return external_single_structure
     */
    public static function change_result(): external_single_structure {
        return new external_single_structure([
            'timemodified' => new external_value(PARAM_INT, 'Last change of the board'),
        ]);
    }
}
