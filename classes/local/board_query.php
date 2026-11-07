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

use stdClass;

/**
 * Reads boards and cards and prepares them for templates and web service clients.
 *
 * Only reads; the structures returned match the external function return definitions in
 * {@see \mod_taskboard\external\structures}.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class board_query {
    /** @var board_access The activity and user the data is prepared for. */
    private board_access $access;

    /**
     * Creates the query object.
     *
     * @param board_access $access The activity and user the data is prepared for.
     */
    public function __construct(board_access $access) {
        $this->access = $access;
    }

    /**
     * Exports a board with its columns and visible cards.
     *
     * @param stdClass $board The board record.
     * @return array
     */
    public function export_board(stdClass $board): array {
        global $DB;
        $access = $this->access;
        $columns = $DB->get_records('taskboard_columns', ['boardid' => $board->id], 'sortorder, id');
        $cards = $DB->get_records('taskboard_cards', ['boardid' => $board->id], 'columnid, sortorder, id');
        $cards = array_filter($cards, fn($card) => $access->can_see_card($card));
        $checks = $this->check_counts(array_keys($cards));
        $links = $this->link_summaries(array_keys($cards));
        $assignees = $this->assignees(array_keys($cards));
        $images = $this->image_urls();

        $bycolumn = [];
        foreach ($cards as $card) {
            $bycolumn[(int)$card->columnid][] = $card;
        }
        $canmanagecolumns = $access->can_manage_columns($board);
        $canadd = $access->can_add_card($board);
        $exported = [];
        $columnlist = array_values($columns);
        foreach ($columnlist as $index => $column) {
            $columncards = [];
            foreach ($bycolumn[(int)$column->id] ?? [] as $card) {
                $columncards[] = $this->export_card_summary(
                    $board,
                    $card,
                    $column,
                    $checks[(int)$card->id] ?? [0, 0],
                    $assignees[(int)$card->id] ?? [],
                    $images[(int)$card->id] ?? null,
                    $links[(int)$card->id] ?? ['label' => '', 'count' => 0]
                );
            }
            $count = count($columncards);
            $limit = (int)$column->wiplimit;
            $exported[] = [
                'id' => (int)$column->id,
                'title' => format_string($column->title, true, ['context' => $access->context]),
                'wiplimit' => $limit,
                'haswiplimit' => $limit > 0,
                'isdone' => (bool)$column->isdone,
                'count' => $count,
                'overlimit' => $limit > 0 && $count > $limit,
                'countlabel' => $limit > 0 ? $count . ' / ' . $limit : (string)$count,
                'canmanage' => $canmanagecolumns,
                'canmoveleft' => $canmanagecolumns && $index > 0,
                'canmoveright' => $canmanagecolumns && $index < count($columnlist) - 1,
                'canadd' => $canadd,
                'cards' => $columncards,
            ];
        }
        return [
            'id' => (int)$board->id,
            'key' => board_service::board_key($board),
            'istemplate' => !empty($board->istemplate),
            'timemodified' => (int)$board->timemodified,
            'canmanagecolumns' => $canmanagecolumns,
            'canadd' => $canadd,
            'columns' => $exported,
        ];
    }

    /**
     * Exports one card in full for the card detail view.
     *
     * @param stdClass $board The board record.
     * @param stdClass $card The card record.
     * @return array
     */
    public function export_card_detail(stdClass $board, stdClass $card): array {
        global $DB;
        $access = $this->access;
        $column = $DB->get_record('taskboard_columns', ['id' => $card->columnid], '*', MUST_EXIST);
        $checks = [];
        $done = 0;
        foreach ($DB->get_records('taskboard_checks', ['cardid' => $card->id], 'sortorder, id') as $check) {
            $done += $check->done ? 1 : 0;
            $checks[] = ['id' => (int)$check->id, 'text' => $check->text, 'done' => (bool)$check->done];
        }
        $assignees = $this->assignees([(int)$card->id])[(int)$card->id] ?? [];
        $links = [];
        foreach ($DB->get_records('taskboard_links', ['cardid' => $card->id], 'sortorder, id') as $link) {
            $links[] = [
                'url' => $link->url,
                'title' => self::link_label($link),
            ];
        }
        $images = $this->image_urls((int)$card->id);
        $author = '';
        if ((int)$card->createdby > 0) {
            $user = \core_user::get_user((int)$card->createdby);
            $author = $user ? fullname($user) : '';
        }
        $videourl = !empty($access->taskboard->allowvideos) ? video_embed::embed_url((string)$card->videourl) : null;
        $total = count($checks);
        return [
            'id' => (int)$card->id,
            'boardid' => (int)$board->id,
            'title' => $card->title,
            'description' => format_text((string)$card->description, FORMAT_PLAIN, ['context' => $access->context]),
            'hasdescription' => trim((string)$card->description) !== '',
            'columntitle' => format_string($column->title, true, ['context' => $access->context]),
            'labels' => $this->export_labels((string)$card->labels),
            'hasduedate' => (int)$card->duedate > 0,
            'duedate' => (int)$card->duedate > 0 ? self::format_date((int)$card->duedate, 'strftimedatefullshort') : '',
            'author' => $author,
            'assignees' => $assignees,
            'hasassignees' => !empty($assignees),
            'checks' => $checks,
            'haschecks' => $total > 0,
            'checksdone' => $done,
            'checkstotal' => $total,
            'checkspercent' => $total > 0 ? (int)round($done / $total * 100) : 0,
            'links' => $links,
            'haslinks' => !empty($links),
            'hasimage' => isset($images[(int)$card->id]),
            'imageurl' => $images[(int)$card->id] ?? '',
            'imagealt' => (string)$card->imagealt,
            'hasvideo' => $videourl !== null,
            'videourl' => $videourl ?? '',
            'approved' => (bool)$card->approved,
            'canapprove' => !$card->approved && $access->can_manage(),
            'canedit' => $access->can_edit_card($board, $card),
            'canmove' => $access->can_move_card($board, $card),
            'canaddimage' => !empty($access->taskboard->allowimages) && $access->can_edit_card($board, $card),
        ];
    }

    /**
     * Counts the cards of a user that lie in done columns, for completion and grading.
     *
     * A card counts for its author and for every assigned user once it is approved.
     *
     * @param int $taskboardid The activity instance id.
     * @param int $userid The user id.
     * @return int
     */
    public static function count_done_cards(int $taskboardid, int $userid): int {
        global $DB;
        $sql = 'SELECT COUNT(DISTINCT c.id)
                  FROM {taskboard_cards} c
                  JOIN {taskboard_columns} col ON col.id = c.columnid
                  JOIN {taskboard_boards} b ON b.id = c.boardid
             LEFT JOIN {taskboard_assignees} a ON a.cardid = c.id AND a.userid = :userid1
                 WHERE b.taskboardid = :taskboardid
                   AND b.istemplate = 0
                   AND col.isdone = 1
                   AND c.approved = 1
                   AND (c.createdby = :userid2 OR a.id IS NOT NULL)';
        return (int)$DB->count_records_sql($sql, [
            'taskboardid' => $taskboardid,
            'userid1' => $userid,
            'userid2' => $userid,
        ]);
    }

    /**
     * Exports the short form of a card shown on the board.
     *
     * @param stdClass $board The board record.
     * @param stdClass $card The card record.
     * @param stdClass $column The column the card is in.
     * @param int[] $checks Done and total checklist items.
     * @param array $assignees Exported assignees.
     * @param string|null $imageurl The image address, if any.
     * @param array $links Label of the first link and number of links.
     * @return array
     */
    private function export_card_summary(
        stdClass $board,
        stdClass $card,
        stdClass $column,
        array $checks,
        array $assignees,
        ?string $imageurl,
        array $links
    ): array {
        $access = $this->access;
        [$done, $total] = $checks;
        $due = (int)$card->duedate;
        $shown = array_slice($assignees, 0, 3);
        $more = count($assignees) - count($shown);
        $aria = [$card->title, get_string('incolumn', 'mod_taskboard', format_string($column->title))];
        if ($imageurl !== null) {
            $aria[] = get_string('imagewithalt', 'mod_taskboard', $card->imagealt !== '' ? $card->imagealt : '-');
        }
        if ($due > 0) {
            $aria[] = get_string('duedatearia', 'mod_taskboard', self::format_date($due, 'strftimedatefullshort'));
        }
        if ($total > 0) {
            $aria[] = get_string('checksaria', 'mod_taskboard', (object)['done' => $done, 'total' => $total]);
        }
        $linkcount = (int)$links['count'];
        if ($linkcount > 0) {
            $aria[] = get_string('linksaria', 'mod_taskboard', $linkcount);
        }
        if (empty($card->approved)) {
            $aria[] = get_string('pendingapproval', 'mod_taskboard');
        }
        return [
            'id' => (int)$card->id,
            'columnid' => (int)$card->columnid,
            'title' => $card->title,
            'arialabel' => implode('. ', $aria) . '.',
            'labels' => $this->export_labels((string)$card->labels),
            'haslabels' => trim((string)$card->labels) !== '',
            'hasduedate' => $due > 0,
            'duedate' => $due > 0 ? self::format_date($due, 'strftimedateshortmonthabbr') : '',
            'overdue' => $due > 0 && $due < time() && empty($column->isdone),
            'haschecks' => $total > 0,
            'checksdone' => $done,
            'checkstotal' => $total,
            'hasimage' => $imageurl !== null,
            'imageurl' => $imageurl ?? '',
            'hasvideo' => !empty($access->taskboard->allowvideos) && trim((string)$card->videourl) !== '',
            'haslinks' => $linkcount > 0,
            'linklabel' => $links['label'],
            'hasmorelinks' => $linkcount > 1,
            'morelinks' => max(0, $linkcount - 1),
            'isdone' => (bool)$column->isdone,
            'pending' => empty($card->approved),
            'assignees' => $shown,
            'hasmoreassignees' => $more > 0,
            'moreassignees' => $more,
            'canmove' => $access->can_move_card($board, $card),
            'canedit' => $access->can_edit_card($board, $card),
        ];
    }

    /**
     * Formats a date with a date format from the language pack.
     *
     * @param int $time The timestamp.
     * @param string $format The langconfig string key of the format.
     * @return string
     */
    private static function format_date(int $time, string $format): string {
        return userdate($time, get_string($format, 'langconfig'));
    }

    /**
     * Exports stored label keys as key and display name.
     *
     * @param string $labels Comma separated label keys.
     * @return array
     */
    private function export_labels(string $labels): array {
        $names = presets::label_options();
        $exported = [];
        foreach (presets::clean_labels(explode(',', $labels)) as $key) {
            $exported[] = ['key' => $key, 'name' => $names[$key]];
        }
        return $exported;
    }

    /**
     * Returns done and total checklist items per card.
     *
     * @param int[] $cardids The card ids.
     * @return array<int, int[]>
     */
    private function check_counts(array $cardids): array {
        global $DB;
        if (!$cardids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($cardids, SQL_PARAMS_NAMED);
        $sql = "SELECT cardid, SUM(done) AS donecount, COUNT(id) AS total
                  FROM {taskboard_checks}
                 WHERE cardid $insql
              GROUP BY cardid";
        $counts = [];
        foreach ($DB->get_records_sql($sql, $params) as $row) {
            $counts[(int)$row->cardid] = [(int)$row->donecount, (int)$row->total];
        }
        return $counts;
    }

    /**
     * Returns, per card, the label of its first link and the number of its links.
     *
     * @param int[] $cardids The card ids.
     * @return array<int, array{label: string, count: int}>
     */
    private function link_summaries(array $cardids): array {
        global $DB;
        if (!$cardids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($cardids, SQL_PARAMS_NAMED);
        $links = $DB->get_records_select('taskboard_links', "cardid $insql", $params, 'cardid, sortorder, id');
        $summaries = [];
        foreach ($links as $link) {
            $cardid = (int)$link->cardid;
            if (!isset($summaries[$cardid])) {
                $summaries[$cardid] = ['label' => self::link_label($link), 'count' => 0];
            }
            $summaries[$cardid]['count']++;
        }
        return $summaries;
    }

    /**
     * Returns the text a link is shown with: its title, or the host name of its address.
     *
     * @param stdClass $link The link record.
     * @return string
     */
    public static function link_label(stdClass $link): string {
        $title = trim((string)$link->title);
        if ($title !== '') {
            return $title;
        }
        $host = (string)parse_url((string)$link->url, PHP_URL_HOST);
        $host = preg_replace('/^www\./i', '', $host);
        return $host !== '' ? $host : (string)$link->url;
    }

    /**
     * Returns the exported assignees per card.
     *
     * @param int[] $cardids The card ids.
     * @return array<int, array>
     */
    private function assignees(array $cardids): array {
        global $DB;
        if (!$cardids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($cardids, SQL_PARAMS_NAMED);
        $userfields = \core_user\fields::for_name()->get_sql('u', true);
        $sql = "SELECT a.id, a.cardid, u.id AS userid {$userfields->selects}
                  FROM {taskboard_assignees} a
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0
                 WHERE a.cardid $insql
              ORDER BY u.lastname, u.firstname, u.id";
        $result = [];
        foreach ($DB->get_records_sql($sql, $params + $userfields->params) as $row) {
            $user = clone $row;
            $user->id = $row->userid;
            $name = fullname($user);
            $result[(int)$row->cardid][] = [
                'userid' => (int)$row->userid,
                'fullname' => $name,
                'initials' => $this->initials($user),
                'colour' => ((int)$row->userid % 6) + 1,
            ];
        }
        return $result;
    }

    /**
     * Returns up to two initials of a user.
     *
     * @param stdClass $user The user with name fields.
     * @return string
     */
    private function initials(stdClass $user): string {
        $first = \core_text::substr(trim((string)($user->firstname ?? '')), 0, 1);
        $last = \core_text::substr(trim((string)($user->lastname ?? '')), 0, 1);
        return \core_text::strtoupper($first . $last);
    }

    /**
     * Returns the image address per card id.
     *
     * @param int|null $cardid Restricts the lookup to one card.
     * @return array<int, string>
     */
    private function image_urls(?int $cardid = null): array {
        $fs = get_file_storage();
        $contextid = $this->access->context->id;
        $files = $fs->get_area_files(
            $contextid,
            'mod_taskboard',
            'cardimage',
            $cardid ?? false,
            'itemid, timemodified DESC',
            false
        );
        $urls = [];
        foreach ($files as $file) {
            $itemid = (int)$file->get_itemid();
            if (isset($urls[$itemid])) {
                continue;
            }
            $urls[$itemid] = \core\url::make_pluginfile_url(
                $contextid,
                'mod_taskboard',
                'cardimage',
                $itemid,
                '/',
                $file->get_filename()
            )->out(false);
        }
        return $urls;
    }
}
