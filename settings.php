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
 * Site settings of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configselect(
        'mod_taskboard/defaultcardsize',
        get_string('defaultcardsize', 'mod_taskboard'),
        get_string('defaultcardsize_desc', 'mod_taskboard'),
        'm',
        \mod_taskboard\local\presets::cardsize_options()
    ));

    $settings->add(new admin_setting_configselect(
        'mod_taskboard/pollinterval',
        get_string('pollinterval', 'mod_taskboard'),
        get_string('pollinterval_desc', 'mod_taskboard'),
        15,
        [
            0 => get_string('never'),
            5 => get_string('numseconds', 'core', 5),
            10 => get_string('numseconds', 'core', 10),
            15 => get_string('numseconds', 'core', 15),
            30 => get_string('numseconds', 'core', 30),
            60 => get_string('numminutes', 'core', 1),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_taskboard/maxbytes',
        get_string('maxbytes', 'mod_taskboard'),
        get_string('maxbytes_desc', 'mod_taskboard'),
        5 * 1024 * 1024,
        get_max_upload_sizes($CFG->maxbytes)
    ));

    $settings->add(new admin_setting_configtextarea(
        'mod_taskboard/peertubehosts',
        get_string('peertubehosts', 'mod_taskboard'),
        get_string('peertubehosts_desc', 'mod_taskboard'),
        '',
        PARAM_TEXT
    ));
}
