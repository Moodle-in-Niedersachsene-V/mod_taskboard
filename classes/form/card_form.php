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

namespace mod_taskboard\form;

use core\context;
use core\url;
use mod_taskboard\local\board_access;
use mod_taskboard\local\board_service;
use mod_taskboard\local\card_data;
use mod_taskboard\local\image_processor;
use mod_taskboard\local\presets;
use mod_taskboard\local\video_embed;

/**
 * Modal form to add or edit a card.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card_form extends \core_form\dynamic_form {
    /** @var board_access|null The resolved activity and user. */
    private ?board_access $access = null;

    /**
     * Returns the access object for the submitted course module.
     *
     * @return board_access
     */
    private function get_access(): board_access {
        if ($this->access === null) {
            $this->access = board_access::from_cmid($this->optional_param('cmid', 0, PARAM_INT));
        }
        return $this->access;
    }

    /**
     * Returns the board and, when editing, the card the form works on.
     *
     * @return array{0: \stdClass, 1: \stdClass|null}
     */
    private function get_target(): array {
        $access = $this->get_access();
        $cardid = $this->optional_param('cardid', 0, PARAM_INT);
        if ($cardid > 0) {
            return $access->get_card($cardid);
        }
        return [$access->get_board($this->optional_param('boardid', 0, PARAM_INT)), null];
    }

    /**
     * Defines the form fields.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $access = $this->get_access();
        $taskboard = $access->taskboard;
        [$board] = $this->get_target();

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'boardid');
        $mform->setType('boardid', PARAM_INT);
        $mform->addElement('hidden', 'cardid');
        $mform->setType('cardid', PARAM_INT);
        $mform->addElement('hidden', 'columnid');
        $mform->setType('columnid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('cardtitle', 'mod_taskboard'), ['maxlength' => 255, 'size' => 40]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('carddescription', 'mod_taskboard'), ['rows' => 4, 'cols' => 40]);
        $mform->setType('description', PARAM_TEXT);

        $labels = [];
        foreach (presets::label_options() as $key => $name) {
            $labels[] = $mform->createElement('advcheckbox', $key, '', $name);
        }
        $mform->addGroup($labels, 'labels', get_string('cardlabels', 'mod_taskboard'), ' ', true);

        $mform->addElement('date_selector', 'duedate', get_string('cardduedate', 'mod_taskboard'), ['optional' => true]);

        $members = (new board_service($access))->board_members($board);
        if ($members) {
            $options = [];
            foreach ($members as $member) {
                $options[$member->id] = fullname($member);
            }
            $mform->addElement('autocomplete', 'assignees', get_string('cardassignees', 'mod_taskboard'), $options, [
                'multiple' => true,
                'noselectionstring' => get_string('noassignees', 'mod_taskboard'),
            ]);
            $mform->setType('assignees', PARAM_INT);
        }

        $mform->addElement('textarea', 'checks', get_string('cardchecks', 'mod_taskboard'), ['rows' => 4, 'cols' => 40]);
        $mform->setType('checks', PARAM_TEXT);
        $mform->addHelpButton('checks', 'cardchecks', 'mod_taskboard');

        $mform->addElement('textarea', 'links', get_string('cardlinks', 'mod_taskboard'), ['rows' => 3, 'cols' => 40]);
        $mform->setType('links', PARAM_TEXT);
        $mform->addHelpButton('links', 'cardlinks', 'mod_taskboard');

        if (!empty($taskboard->allowimages)) {
            $mform->addElement('filemanager', 'image', get_string('cardimage', 'mod_taskboard'), null, $this->file_options());
            $mform->addHelpButton('image', 'cardimage', 'mod_taskboard');
            $mform->addElement('text', 'imagealt', get_string('cardimagealt', 'mod_taskboard'), ['maxlength' => 255, 'size' => 40]);
            $mform->setType('imagealt', PARAM_TEXT);
            $mform->addHelpButton('imagealt', 'cardimagealt', 'mod_taskboard');
        }

        if (!empty($taskboard->allowvideos)) {
            // The URL element offers the file picker, so videos come from the PeerTube repository,
            // including unlisted ones; the repository must return links (FILE_EXTERNAL).
            self::load_repository_lib();
            $mform->addElement('url', 'videourl', get_string('cardvideo', 'mod_taskboard'), ['size' => 40], [
                'usefilepicker' => true,
            ]);
            $mform->setType('videourl', PARAM_URL);
            $mform->addHelpButton('videourl', 'cardvideo', 'mod_taskboard');
        }
    }

    /**
     * Loads the repository library with the file picker and the FILE_* constants.
     *
     * When the form is built in an AJAX request for the modal, nothing else has loaded it yet.
     *
     * @return void
     */
    private static function load_repository_lib(): void {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');
    }

    /**
     * Returns the file manager options for the card image.
     *
     * @return array
     */
    private function file_options(): array {
        self::load_repository_lib();
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'maxbytes' => image_processor::max_bytes(get_course($this->get_access()->cm->course)),
            'accepted_types' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
            'return_types' => FILE_INTERNAL,
        ];
    }

    /**
     * Validates the submitted values.
     *
     * @param array $data The submitted values.
     * @param array $files The submitted files.
     * @return array Errors by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $taskboard = $this->get_access()->taskboard;
        if (trim($data['title'] ?? '') === '') {
            $errors['title'] = get_string('required');
        }
        if (!empty($taskboard->allowimages) && !empty($data['image'])) {
            if (!image_processor::draft_images_readable((int)$data['image'])) {
                $errors['image'] = get_string('invalidimage', 'mod_taskboard');
            }
            $draftfiles = file_get_all_files_in_draftarea((int)$data['image']);
            if (!empty($taskboard->requirealt) && $draftfiles && trim($data['imagealt'] ?? '') === '') {
                $errors['imagealt'] = get_string('imagealtrequired', 'mod_taskboard');
            }
        }
        $linklines = card_data::link_lines((string)($data['links'] ?? ''));
        if (count($linklines) > card_data::MAX_LINKS) {
            $errors['links'] = get_string('toomanylinks', 'mod_taskboard', card_data::MAX_LINKS);
        } else {
            foreach ($linklines as $number => $line) {
                if (card_data::parse_link($line) === null) {
                    $errors['links'] = get_string('invalidlink', 'mod_taskboard', $number + 1);
                    break;
                }
            }
        }
        if (
            !empty($taskboard->allowvideos) && trim($data['videourl'] ?? '') !== ''
                && !video_embed::is_valid($data['videourl'])
        ) {
            $errors['videourl'] = get_string('invalidvideo', 'mod_taskboard');
        }
        return $errors;
    }

    /**
     * Returns the module context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return $this->get_access()->context;
    }

    /**
     * Checks the user may add or edit the card.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        $access = $this->get_access();
        $access->require_view();
        [$board, $card] = $this->get_target();
        $allowed = $card ? $access->can_edit_card($board, $card) : $access->can_add_card($board);
        if (!$allowed) {
            throw new \moodle_exception('nopermissioncard', 'mod_taskboard');
        }
    }

    /**
     * Saves the card.
     *
     * @return array The id of the saved card.
     */
    public function process_dynamic_submission() {
        $access = $this->get_access();
        $taskboard = $access->taskboard;
        $data = $this->get_data();
        [$board, $card] = $this->get_target();

        $members = (new board_service($access))->board_members($board);
        $assignees = array_values(array_filter(
            array_map('intval', (array)($data->assignees ?? [])),
            fn($userid) => isset($members[$userid])
        ));
        $labels = array_keys(array_filter((array)($data->labels ?? [])));
        $content = new card_data(
            title: (string)$data->title,
            description: (string)($data->description ?? ''),
            labels: $labels,
            duedate: (int)($data->duedate ?? 0),
            videourl: !empty($taskboard->allowvideos) ? (string)($data->videourl ?? '') : (string)($card->videourl ?? ''),
            imagealt: !empty($taskboard->allowimages) ? (string)($data->imagealt ?? '') : (string)($card->imagealt ?? ''),
            assignees: $assignees,
            checks: card_data::checks_from_text((string)($data->checks ?? '')),
            links: card_data::links_from_text((string)($data->links ?? '')),
        );

        $service = new board_service($access);
        if ($card) {
            $service->update_card($card, $content);
            $cardid = (int)$card->id;
        } else {
            $cardid = $service->add_card($board, (int)$data->columnid, $content);
        }

        if (!empty($taskboard->allowimages) && isset($data->image)) {
            file_save_draft_area_files(
                (int)$data->image,
                $access->context->id,
                'mod_taskboard',
                'cardimage',
                $cardid,
                $this->file_options()
            );
            image_processor::sanitise_card_image($access->context, $cardid);
        }
        return ['cardid' => $cardid];
    }

    /**
     * Loads the card into the form when editing.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;
        $access = $this->get_access();
        [$board, $card] = $this->get_target();
        $data = [
            'cmid' => $access->cm->id,
            'boardid' => $board->id,
            'cardid' => $card ? $card->id : 0,
            'columnid' => $card ? $card->columnid : $this->optional_param('columnid', 0, PARAM_INT),
        ];
        if ($card) {
            $labels = [];
            foreach (presets::clean_labels(explode(',', (string)$card->labels)) as $key) {
                $labels[$key] = 1;
            }
            $checks = $DB->get_fieldset_sql(
                'SELECT text FROM {taskboard_checks} WHERE cardid = :cardid ORDER BY sortorder, id',
                ['cardid' => $card->id]
            );
            $links = [];
            foreach ($DB->get_records('taskboard_links', ['cardid' => $card->id], 'sortorder, id') as $link) {
                $title = trim((string)$link->title);
                $links[] = $title !== '' ? $title . ' | ' . $link->url : $link->url;
            }
            $data += [
                'links' => implode("\n", $links),
                'title' => $card->title,
                'description' => $card->description,
                'labels' => $labels,
                'duedate' => (int)$card->duedate,
                'assignees' => $DB->get_fieldset_select(
                    'taskboard_assignees',
                    'userid',
                    'cardid = :cardid',
                    ['cardid' => $card->id]
                ),
                'checks' => implode("\n", $checks),
                'imagealt' => $card->imagealt,
                'videourl' => $card->videourl,
            ];
        }
        if (!empty($access->taskboard->allowimages)) {
            $draftitemid = file_get_submitted_draft_itemid('image');
            file_prepare_draft_area(
                $draftitemid,
                $access->context->id,
                'mod_taskboard',
                'cardimage',
                $card ? $card->id : null,
                $this->file_options()
            );
            $data['image'] = $draftitemid;
        }
        $this->set_data($data);
    }

    /**
     * Returns the page the form belongs to.
     *
     * @return url
     */
    protected function get_page_url_for_dynamic_submission(): url {
        return new url('/mod/taskboard/view.php', ['id' => $this->get_access()->cm->id]);
    }
}
