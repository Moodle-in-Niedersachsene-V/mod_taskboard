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
 * Fixed lists the plugin offers: column templates, card labels, card sizes and student rights.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presets {
    /** @var int One board shared by everyone in the course. */
    public const BOARDTYPE_COURSE = 0;

    /** @var int One board per group. */
    public const BOARDTYPE_GROUP = 1;

    /** @var int One personal board per user. */
    public const BOARDTYPE_PERSONAL = 2;

    /** @var string Students may only move cards. */
    public const RIGHTS_MOVE = 'move';

    /** @var string Students may add cards and edit their own cards. */
    public const RIGHTS_OWN = 'own';

    /** @var string Students may edit all cards and columns. */
    public const RIGHTS_ALL = 'all';

    /** @var string[] Allowed card sizes. */
    public const CARDSIZES = ['auto', 's', 'm', 'l'];

    /** @var string[] Allowed label keys, in display order. */
    public const LABELS = ['idea', 'research', 'presentation', 'important', 'helpneeded'];

    /**
     * Returns the column templates as key => list of column definitions.
     *
     * Each column definition holds the language string key of its title, whether it takes the
     * work-in-progress limit and whether cards in it count as done.
     *
     * @return array<string, array<int, array{title: string, wip: bool, done: bool}>>
     */
    public static function column_templates(): array {
        return [
            'classic' => [
                ['title' => 'col_todo', 'wip' => false, 'done' => false],
                ['title' => 'col_doing', 'wip' => true, 'done' => false],
                ['title' => 'col_done', 'wip' => false, 'done' => true],
            ],
            'ideas' => [
                ['title' => 'col_ideas', 'wip' => false, 'done' => false],
                ['title' => 'col_todo', 'wip' => false, 'done' => false],
                ['title' => 'col_doing', 'wip' => true, 'done' => false],
                ['title' => 'col_done', 'wip' => false, 'done' => true],
            ],
            'project' => [
                ['title' => 'col_plan', 'wip' => false, 'done' => false],
                ['title' => 'col_research', 'wip' => true, 'done' => false],
                ['title' => 'col_create', 'wip' => true, 'done' => false],
                ['title' => 'col_present', 'wip' => false, 'done' => false],
                ['title' => 'col_done', 'wip' => false, 'done' => true],
            ],
            'stations' => [
                ['title' => 'col_open', 'wip' => false, 'done' => false],
                ['title' => 'col_doing', 'wip' => true, 'done' => false],
                ['title' => 'col_check', 'wip' => false, 'done' => false],
                ['title' => 'col_done', 'wip' => false, 'done' => true],
            ],
        ];
    }

    /**
     * Returns the column template options for a select element.
     *
     * @return array<string, string>
     */
    public static function column_template_options(): array {
        $options = [];
        foreach (array_keys(self::column_templates()) as $key) {
            $options[$key] = get_string('template_' . $key, 'mod_taskboard');
        }
        return $options;
    }

    /**
     * Returns the card size options for a select element.
     *
     * @return array<string, string>
     */
    public static function cardsize_options(): array {
        $options = [];
        foreach (self::CARDSIZES as $size) {
            $options[$size] = get_string('cardsize_' . $size, 'mod_taskboard');
        }
        return $options;
    }

    /**
     * Returns the label options as key => display name.
     *
     * @return array<string, string>
     */
    public static function label_options(): array {
        $options = [];
        foreach (self::LABELS as $label) {
            $options[$label] = get_string('label_' . $label, 'mod_taskboard');
        }
        return $options;
    }

    /**
     * Reduces a list of label keys to the known ones, in display order.
     *
     * @param string[] $labels Label keys from storage or input.
     * @return string[]
     */
    public static function clean_labels(array $labels): array {
        return array_values(array_intersect(self::LABELS, $labels));
    }
}
