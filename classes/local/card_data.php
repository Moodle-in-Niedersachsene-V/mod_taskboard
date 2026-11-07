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
     * @param array[] $links Links in order, each with url and title.
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
        /** @var array[] Links in order, each with url and title. */
        public readonly array $links = [],
    ) {
    }

    /** @var int Most links one card can hold. */
    public const MAX_LINKS = 10;

    /**
     * Reads one line of the links field: an address, or a title and an address separated by "|".
     *
     * @param string $line The line.
     * @return array|null The link with url and title, null if the address is not a valid http(s) address.
     */
    public static function parse_link(string $line): ?array {
        $title = '';
        $address = $line;
        if (str_contains($line, '|')) {
            [$title, $address] = array_map('trim', explode('|', $line, 2));
        }
        $url = clean_param(trim($address), PARAM_URL);
        if ($url === '' || !preg_match('~^https?://[^\s/]+~i', $url)) {
            return null;
        }
        return ['url' => $url, 'title' => \core_text::substr($title, 0, 255)];
    }

    /**
     * Returns the non-empty lines of the links field.
     *
     * @param string $text The field text.
     * @return string[]
     */
    public static function link_lines(string $text): array {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn($line) => $line !== ''));
    }

    /**
     * Turns the links field into links, skipping lines that are not valid addresses.
     *
     * @param string $text The field text, one link per line.
     * @return array[]
     */
    public static function links_from_text(string $text): array {
        $links = [];
        foreach (self::link_lines($text) as $line) {
            $link = self::parse_link($line);
            if ($link !== null && count($links) < self::MAX_LINKS) {
                $links[] = $link;
            }
        }
        return $links;
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
