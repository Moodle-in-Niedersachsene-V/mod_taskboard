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

namespace mod_taskboard\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_taskboard\local\board_service;

/**
 * Privacy provider of mod_taskboard.
 *
 * Personal boards belong to their user and are deleted with them. Cards on shared boards are group
 * work: when their author's data is deleted, the card stays and loses its author.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describes the personal data the plugin stores.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('taskboard_boards', [
            'userid' => 'privacy:metadata:boards:userid',
            'timecreated' => 'privacy:metadata:boards:timecreated',
        ], 'privacy:metadata:boards');
        $collection->add_database_table('taskboard_cards', [
            'createdby' => 'privacy:metadata:cards:createdby',
            'title' => 'privacy:metadata:cards:title',
            'description' => 'privacy:metadata:cards:description',
            'imagealt' => 'privacy:metadata:cards:imagealt',
            'videourl' => 'privacy:metadata:cards:videourl',
            'timecreated' => 'privacy:metadata:cards:timecreated',
            'timemodified' => 'privacy:metadata:cards:timemodified',
        ], 'privacy:metadata:cards');
        $collection->add_database_table('taskboard_assignees', [
            'userid' => 'privacy:metadata:assignees:userid',
        ], 'privacy:metadata:assignees');
        $collection->add_user_preference('mod_taskboard_cardsize', 'privacy:metadata:preference:cardsize');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        return $collection;
    }

    /**
     * Returns the SQL that finds the module contexts a user has data in, with its parameters.
     *
     * @param int $userid The user id.
     * @return array{0: string, 1: array}
     */
    private static function user_context_sql(int $userid): array {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'taskboard'
                  JOIN {taskboard_boards} b ON b.taskboardid = cm.instance
             LEFT JOIN {taskboard_cards} c ON c.boardid = b.id
             LEFT JOIN {taskboard_assignees} a ON a.cardid = c.id
                 WHERE b.userid = :userid1 OR c.createdby = :userid2 OR a.userid = :userid3";
        return [$sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]];
    }

    /**
     * Returns the contexts with data of a user.
     *
     * @param int $userid The user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        [$sql, $params] = self::user_context_sql($userid);
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Adds the users with data in a context to the list.
     *
     * @param userlist $userlist The list to fill.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \core\context\module) {
            return;
        }
        $params = ['cmid' => $context->instanceid, 'modname' => 'taskboard'];
        $base = "FROM {course_modules} cm
                 JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 JOIN {taskboard_boards} b ON b.taskboardid = cm.instance";
        $userlist->add_from_sql('userid', "SELECT b.userid $base WHERE cm.id = :cmid AND b.userid > 0", $params);
        $userlist->add_from_sql(
            'createdby',
            "SELECT c.createdby $base JOIN {taskboard_cards} c ON c.boardid = b.id WHERE cm.id = :cmid AND c.createdby > 0",
            $params
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT a.userid $base JOIN {taskboard_cards} c ON c.boardid = b.id
                JOIN {taskboard_assignees} a ON a.cardid = c.id WHERE cm.id = :cmid",
            $params
        );
    }

    /**
     * Exports the data of a user in the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \core\context\module) {
                continue;
            }
            $cm = get_coursemodule_from_id('taskboard', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $sql = "SELECT c.*, col.title AS columntitle, b.userid AS boarduserid
                      FROM {taskboard_cards} c
                      JOIN {taskboard_boards} b ON b.id = c.boardid
                      JOIN {taskboard_columns} col ON col.id = c.columnid
                 LEFT JOIN {taskboard_assignees} a ON a.cardid = c.id AND a.userid = :userid1
                     WHERE b.taskboardid = :taskboardid
                       AND (c.createdby = :userid2 OR a.id IS NOT NULL OR b.userid = :userid3)
                  ORDER BY c.id";
            $cards = $DB->get_records_sql($sql, [
                'taskboardid' => $cm->instance,
                'userid1' => $userid,
                'userid2' => $userid,
                'userid3' => $userid,
            ]);
            $exported = [];
            foreach ($cards as $card) {
                $checks = $DB->get_records('taskboard_checks', ['cardid' => $card->id], 'sortorder', 'text, done');
                $exported[] = (object)[
                    'title' => $card->title,
                    'description' => $card->description,
                    'column' => $card->columntitle,
                    'labels' => $card->labels,
                    'duedate' => $card->duedate ? transform::datetime($card->duedate) : '',
                    'videourl' => $card->videourl,
                    'imagealt' => $card->imagealt,
                    'createdbyyou' => transform::yesno((int)$card->createdby === $userid),
                    'onpersonalboard' => transform::yesno((int)$card->boarduserid === $userid),
                    'checklist' => array_values(array_map(
                        fn($check) => ['text' => $check->text, 'done' => transform::yesno((bool)$check->done)],
                        $checks
                    )),
                    'timecreated' => transform::datetime($card->timecreated),
                    'timemodified' => transform::datetime($card->timemodified),
                ];
                writer::with_context($context)->export_area_files(
                    [get_string('privacy:cards', 'mod_taskboard'), $card->id],
                    'mod_taskboard',
                    'cardimage',
                    $card->id
                );
            }
            if ($exported) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:cards', 'mod_taskboard')],
                    (object)['cards' => $exported]
                );
            }
        }
    }

    /**
     * Exports the card size preferences of a user.
     *
     * @param int $userid The user id.
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        $preferences = get_user_preferences(null, null, $userid);
        foreach ($preferences as $name => $value) {
            if (str_starts_with($name, 'mod_taskboard_cardsize_')) {
                writer::export_user_preference(
                    'mod_taskboard',
                    $name,
                    $value,
                    get_string('privacy:metadata:preference:cardsize', 'mod_taskboard')
                );
            }
        }
    }

    /**
     * Deletes all user data in a context.
     *
     * @param \context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if (!$context instanceof \core\context\module) {
            return;
        }
        $cm = get_coursemodule_from_id('taskboard', $context->instanceid);
        if (!$cm) {
            return;
        }
        board_service::delete_all_boards((int)$cm->instance, $context);
    }

    /**
     * Deletes the data of one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_users_in_context($context, [$userid]);
        }
    }

    /**
     * Deletes the data of several users in one context.
     *
     * @param approved_userlist $userlist The approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        self::delete_users_in_context($userlist->get_context(), $userlist->get_userids());
    }

    /**
     * Deletes personal boards, removes assignments and anonymises cards of the given users.
     *
     * @param \context $context The module context.
     * @param int[] $userids The user ids.
     * @return void
     */
    private static function delete_users_in_context(\context $context, array $userids): void {
        global $DB;
        if (!$context instanceof \core\context\module || !$userids) {
            return;
        }
        $cm = get_coursemodule_from_id('taskboard', $context->instanceid);
        if (!$cm) {
            return;
        }
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $boardids = $DB->get_fieldset_select(
            'taskboard_boards',
            'id',
            "taskboardid = :taskboardid AND userid $usersql",
            ['taskboardid' => $cm->instance] + $userparams
        );
        $fs = get_file_storage();
        foreach ($boardids as $boardid) {
            foreach ($DB->get_fieldset_select('taskboard_cards', 'id', 'boardid = :boardid', ['boardid' => $boardid]) as $cardid) {
                $fs->delete_area_files($context->id, 'mod_taskboard', 'cardimage', $cardid);
            }
            board_service::delete_board_rows((int)$boardid);
        }
        $cardsql = "SELECT c.id
                      FROM {taskboard_cards} c
                      JOIN {taskboard_boards} b ON b.id = c.boardid
                     WHERE b.taskboardid = :taskboardid";
        $DB->delete_records_select(
            'taskboard_assignees',
            "userid $usersql AND cardid IN ($cardsql)",
            $userparams + ['taskboardid' => $cm->instance]
        );
        $authored = $DB->get_fieldset_sql(
            "SELECT c.id
               FROM {taskboard_cards} c
               JOIN {taskboard_boards} b ON b.id = c.boardid
              WHERE b.taskboardid = :taskboardid AND c.createdby $usersql",
            ['taskboardid' => $cm->instance] + $userparams
        );
        if ($authored) {
            [$cardinsql, $cardparams] = $DB->get_in_or_equal($authored, SQL_PARAMS_NAMED, 'card');
            $DB->set_field_select('taskboard_cards', 'createdby', 0, "id $cardinsql", $cardparams);
        }
    }
}
