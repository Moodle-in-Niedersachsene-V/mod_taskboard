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
 * Lists the task boards of a course.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$coursecontext = \core\context\course::instance($course->id);
$event = \mod_taskboard\event\course_module_instance_list_viewed::create(['context' => $coursecontext]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url(new \core\url('/mod/taskboard/index.php', ['id' => $course->id]));
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'mod_taskboard'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

$modinfo = get_fast_modinfo($course);
$table = new \core_table\output\html_table();
$table->head = [get_string('name'), get_string('moduleintro')];
foreach ($modinfo->get_instances_of('taskboard') as $cm) {
    if (!$cm->uservisible) {
        continue;
    }
    $link = \core\output\html_writer::link($cm->url, $cm->get_formatted_name());
    $table->data[] = [$link, $cm->get_formatted_content()];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_taskboard'));
if (empty($table->data)) {
    echo $OUTPUT->notification(get_string('noboards', 'mod_taskboard'), 'info');
} else {
    echo \core\output\html_writer::table($table);
}
echo $OUTPUT->footer();
