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
 * Library functions for mod_taskboard.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_taskboard\local\board_access;
use mod_taskboard\local\board_query;
use mod_taskboard\local\board_service;

/**
 * Returns whether the module supports a feature.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True or a value if supported, null if unknown.
 */
function taskboard_supports($feature) {
    return match ($feature) {
        FEATURE_GROUPS, FEATURE_GROUPINGS, FEATURE_MOD_INTRO, FEATURE_SHOW_DESCRIPTION,
        FEATURE_COMPLETION_TRACKS_VIEWS, FEATURE_COMPLETION_HAS_RULES, FEATURE_GRADE_HAS_GRADE,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        default => null,
    };
}

/**
 * Adds a new activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_taskboard_mod_form|null $mform The form.
 * @return int The new instance id.
 */
function taskboard_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    taskboard_normalise_instance($data);
    $data->id = $DB->insert_record('taskboard', $data);
    taskboard_grade_item_update($data);
    return $data->id;
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_taskboard_mod_form|null $mform The form.
 * @return bool True on success.
 */
function taskboard_update_instance($data, $mform = null) {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    taskboard_normalise_instance($data);
    $DB->update_record('taskboard', $data);
    $taskboard = $DB->get_record('taskboard', ['id' => $data->id], '*', MUST_EXIST);
    taskboard_grade_item_update($taskboard);
    taskboard_update_grades($taskboard);
    return true;
}

/**
 * Brings submitted instance settings into their stored form.
 *
 * @param stdClass $data Form data, changed in place.
 * @return void
 */
function taskboard_normalise_instance(stdClass $data): void {
    $data->completioncards = empty($data->completioncards) ? 0 : max(0, (int)$data->completioncards);
    $data->wiplimit = max(0, (int)($data->wiplimit ?? 0));
    $data->grade = (int)($data->grade ?? 0);
}

/**
 * Deletes an activity instance with all its boards.
 *
 * @param int $id The instance id.
 * @return bool True on success.
 */
function taskboard_delete_instance($id) {
    global $DB;
    $taskboard = $DB->get_record('taskboard', ['id' => $id]);
    if (!$taskboard) {
        return false;
    }
    $cm = get_coursemodule_from_instance('taskboard', $id);
    if ($cm) {
        board_service::delete_all_boards((int)$id, \core\context\module::instance($cm->id));
    }
    taskboard_grade_item_delete($taskboard);
    $DB->delete_records('taskboard', ['id' => $id]);
    return true;
}

/**
 * Returns cached course module information, including the custom completion rules.
 *
 * @param stdClass $coursemodule The course module.
 * @return cached_cm_info|false
 */
function taskboard_get_coursemodule_info($coursemodule) {
    global $DB;
    $fields = 'id, name, intro, introformat, completioncards';
    $taskboard = $DB->get_record('taskboard', ['id' => $coursemodule->instance], $fields);
    if (!$taskboard) {
        return false;
    }
    $result = new cached_cm_info();
    $result->name = $taskboard->name;
    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('taskboard', $taskboard, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completioncards'] = (int)$taskboard->completioncards;
    }
    return $result;
}

/**
 * Serves the card images and the intro files.
 *
 * @param stdClass $course The course.
 * @param stdClass $cm The course module.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args The remaining path arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool False if the file was not found.
 */
function mod_taskboard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;
    if ($context->contextlevel != CONTEXT_MODULE || $filearea !== 'cardimage') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/taskboard:view', $context);
    $access = board_access::from_cmid((int)$cm->id);

    $cardid = (int)array_shift($args);
    $card = $DB->get_record('taskboard_cards', ['id' => $cardid]);
    if (!$card) {
        return false;
    }
    $board = $DB->get_record('taskboard_boards', ['id' => $card->boardid, 'taskboardid' => $cm->instance]);
    if (!$board || !$access->can_view_board($board) || !$access->can_see_card($card)) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'mod_taskboard', 'cardimage', $cardid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * Creates or updates the grade item of an instance.
 *
 * @param stdClass $taskboard The instance record (with cmidnumber when coming from the form).
 * @param mixed $grades Grades to write, 'reset' to reset, null for none.
 * @return int GRADE_UPDATE_OK or an error code.
 */
function taskboard_grade_item_update($taskboard, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = ['itemname' => $taskboard->name];
    if (isset($taskboard->cmidnumber)) {
        $params['idnumber'] = $taskboard->cmidnumber;
    }
    if ((int)$taskboard->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = (int)$taskboard->grade;
        $params['grademin'] = 0;
    } else if ((int)$taskboard->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -(int)$taskboard->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/taskboard', $taskboard->course, 'mod', 'taskboard', $taskboard->id, 0, $grades, $params);
}

/**
 * Deletes the grade item of an instance.
 *
 * @param stdClass $taskboard The instance record.
 * @return int GRADE_UPDATE_OK or an error code.
 */
function taskboard_grade_item_delete($taskboard) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/taskboard', $taskboard->course, 'mod', 'taskboard', $taskboard->id, 0, null, ['deleted' => 1]);
}

/**
 * Returns the automatic grades of users: the share of the required done cards, times the maximum grade.
 *
 * Only point grades with a completion target are calculated; otherwise teachers grade in the gradebook.
 *
 * @param stdClass $taskboard The instance record.
 * @param int $userid A user id, or 0 for all users with cards.
 * @return array Grades keyed by user id.
 */
function taskboard_get_user_grades($taskboard, $userid = 0) {
    global $DB;
    if ((int)$taskboard->grade <= 0 || (int)$taskboard->completioncards <= 0) {
        return [];
    }
    if ($userid) {
        $userids = [(int)$userid];
    } else {
        $sql = 'SELECT DISTINCT c.createdby
                  FROM {taskboard_cards} c
                  JOIN {taskboard_boards} b ON b.id = c.boardid
                 WHERE b.taskboardid = :id1 AND c.createdby > 0
                 UNION
                SELECT DISTINCT a.userid
                  FROM {taskboard_assignees} a
                  JOIN {taskboard_cards} c ON c.id = a.cardid
                  JOIN {taskboard_boards} b ON b.id = c.boardid
                 WHERE b.taskboardid = :id2';
        $userids = array_map('intval', $DB->get_fieldset_sql($sql, ['id1' => $taskboard->id, 'id2' => $taskboard->id]));
    }
    $grades = [];
    foreach ($userids as $id) {
        $done = board_query::count_done_cards((int)$taskboard->id, $id);
        $ratio = min(1, $done / (int)$taskboard->completioncards);
        $grades[$id] = (object)['userid' => $id, 'rawgrade' => round($ratio * (int)$taskboard->grade, 2)];
    }
    return $grades;
}

/**
 * Writes the automatic grades to the gradebook.
 *
 * @param stdClass $taskboard The instance record.
 * @param int $userid A user id, or 0 for all users.
 * @param bool $nullifnone Whether to write an empty grade when the user has none.
 * @return void
 */
function taskboard_update_grades($taskboard, $userid = 0, $nullifnone = true) {
    if ((int)$taskboard->grade <= 0 || (int)$taskboard->completioncards <= 0) {
        taskboard_grade_item_update($taskboard);
        return;
    }
    $grades = taskboard_get_user_grades($taskboard, $userid);
    if ($grades) {
        taskboard_grade_item_update($taskboard, $grades);
    } else if ($userid && $nullifnone) {
        taskboard_grade_item_update($taskboard, (object)['userid' => $userid, 'rawgrade' => null]);
    } else {
        taskboard_grade_item_update($taskboard);
    }
}
