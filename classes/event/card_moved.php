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
 * Event raised when a card was moved.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card_moved extends board_event_base {
    /**
     * Sets the basic event properties.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'taskboard_cards';
    }

    /**
     * Returns the localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcardmoved', 'mod_taskboard');
    }

    /**
     * Returns a description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' moved"
            . " the card with id '$this->objectid' in the task board with course module id '$this->contextinstanceid'.";
    }

    /**
     * Returns how the object id is mapped on restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'taskboard_cards', 'restore' => 'taskboard_card'];
    }

    /**
     * Creates the event for a moved card.
     *
     * @param \context $context The module context.
     * @param \stdClass $card The card record after the move.
     * @param int $fromcolumnid The column the card came from.
     * @param int $position The new position in the target column.
     * @return self
     */
    public static function create_for_move(\context $context, \stdClass $card, int $fromcolumnid, int $position): self {
        return self::create([
            'context' => $context,
            'objectid' => $card->id,
            'other' => [
                'boardid' => (int)$card->boardid,
                'fromcolumnid' => $fromcolumnid,
                'tocolumnid' => (int)$card->columnid,
                'position' => $position,
            ],
        ]);
    }

    /**
     * Returns how the ids in other are mapped on restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return parent::get_other_mapping() + [
            'fromcolumnid' => ['db' => 'taskboard_columns', 'restore' => 'taskboard_column'],
            'tocolumnid' => ['db' => 'taskboard_columns', 'restore' => 'taskboard_column'],
            'position' => \core\event\base::NOT_MAPPED,
        ];
    }
}
