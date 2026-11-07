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
 * Internal functions of mod_taskboard used by its pages.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Records that the user viewed the activity: raises the event and marks view completion.
 *
 * @param \mod_taskboard\local\board_access $access The activity and user.
 * @param stdClass $course The course record.
 * @return void
 */
function taskboard_record_view(\mod_taskboard\local\board_access $access, stdClass $course): void {
    $event = \mod_taskboard\event\course_module_viewed::create([
        'objectid' => $access->taskboard->id,
        'context' => $access->context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('taskboard', $access->taskboard);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($access->cm);
}
