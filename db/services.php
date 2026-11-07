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
 * External functions of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_taskboard_get_board' => [
        'classname' => \mod_taskboard\external\get_board::class,
        'description' => 'Returns a board with its columns and cards.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:view',
    ],
    'mod_taskboard_get_card' => [
        'classname' => \mod_taskboard\external\get_card::class,
        'description' => 'Returns one card in full.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:view',
    ],
    'mod_taskboard_move_card' => [
        'classname' => \mod_taskboard\external\move_card::class,
        'description' => 'Moves a card to a position in a column.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_delete_card' => [
        'classname' => \mod_taskboard\external\delete_card::class,
        'description' => 'Deletes a card.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_set_check' => [
        'classname' => \mod_taskboard\external\set_check::class,
        'description' => 'Ticks or unticks a checklist item.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_approve_card' => [
        'classname' => \mod_taskboard\external\approve_card::class,
        'description' => 'Approves a card that waits for approval.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:manage',
    ],
    'mod_taskboard_set_card_image' => [
        'classname' => \mod_taskboard\external\set_card_image::class,
        'description' => 'Stores an image sent as data as the card image.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_move_column' => [
        'classname' => \mod_taskboard\external\move_column::class,
        'description' => 'Moves a column one place to the left or right.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_delete_column' => [
        'classname' => \mod_taskboard\external\delete_column::class,
        'description' => 'Deletes a column with its cards.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:contribute',
    ],
    'mod_taskboard_set_cardsize' => [
        'classname' => \mod_taskboard\external\set_cardsize::class,
        'description' => 'Stores the card size the user prefers.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/taskboard:view',
    ],
];
