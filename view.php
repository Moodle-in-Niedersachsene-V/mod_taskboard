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
 * Shows a task board.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/taskboard/locallib.php');

$id = required_param('id', PARAM_INT);
$selection = optional_param('board', '', PARAM_ALPHANUM);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'taskboard');
require_login($course, true, $cm);

$access = \mod_taskboard\local\board_access::from_cmid($cm->id);
$access->require_view();

$PAGE->set_url(new \core\url('/mod/taskboard/view.php', ['id' => $cm->id]));
$PAGE->set_title(format_string($access->taskboard->name));
$PAGE->set_heading(format_string($course->fullname));

taskboard_record_view($access, $course);

$board = (new \mod_taskboard\local\board_service($access))->resolve_board($selection);
$page = new \mod_taskboard\output\board_page($access, $board);

$PAGE->requires->js_call_amd('mod_taskboard/board', 'init', [
    (int)$cm->id,
    (int)$board->id,
    (int)$board->timemodified,
    (int)get_config('mod_taskboard', 'pollinterval'),
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_taskboard/board', $page->export_for_template($OUTPUT));
echo $OUTPUT->footer();
