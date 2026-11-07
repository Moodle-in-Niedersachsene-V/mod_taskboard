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
 * Restore task of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/taskboard/backup/moodle2/restore_taskboard_stepslib.php');

/**
 * Restore task of mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_taskboard_activity_task extends restore_activity_task {
    /**
     * Defines the settings of this task; there are none.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the steps of this task.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_taskboard_activity_structure_step('taskboard_structure', 'taskboard.xml'));
    }

    /**
     * Returns the contents whose links are decoded.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('taskboard', ['intro'], 'taskboard')];
    }

    /**
     * Returns the rules that decode links.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('TASKBOARDVIEWBYID', '/mod/taskboard/view.php?id=$1', 'course_module'),
            new restore_decode_rule('TASKBOARDINDEX', '/mod/taskboard/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Returns the log rules of the activity.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('taskboard', 'view', 'view.php?id={course_module}', '{taskboard}'),
        ];
    }

    /**
     * Returns the log rules of the course.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [new restore_log_rule('taskboard', 'view all', 'index.php?id={course}', null)];
    }
}
