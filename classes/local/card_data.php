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

/**
 * The editable content of a card, as entered in the card form.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card_data {
    /**
     * Creates the card content.
     *
     * @param string $title The card title.
     * @param string $description Plain text description.
     * @param string[] $labels Label keys from {@see presets::LABELS}.
     * @param int $duedate Due date as timestamp, 0 for none.
     * @param string $videourl Video address, empty for none.
     * @param string $imagealt Alternative text of the card image.
     * @param int[] $assignees User ids assigned to the card.
     * @param string[] $checks Checklist item texts in order.
     */
    public function __construct(
        /** @var string The card title. */
        public readonly string $title,
        /** @var string Plain text description. */
        public readonly string $description = '',
        /** @var string[] Label keys. */
        public readonly array $labels = [],
        /** @var int Due date as timestamp, 0 for none. */
        public readonly int $duedate = 0,
        /** @var string Video address, empty for none. */
        public readonly string $videourl = '',
        /** @var string Alternative text of the card image. */
        public readonly string $imagealt = '',
        /** @var int[] User ids assigned to the card. */
        public readonly array $assignees = [],
        /** @var string[] Checklist item texts in order. */
        public readonly array $checks = [],
    ) {
    }

    /**
     * Splits a multi-line text into checklist items, one per non-empty line.
     *
     * @param string $text The text with one item per line.
     * @return string[]
     */
    public static function checks_from_text(string $text): array {
        $items = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = \core_text::substr($line, 0, 255);
            }
        }
        return $items;
    }
}
