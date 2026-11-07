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

/**
 * Modal form to add or edit a column.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class column_form extends \core_form\dynamic_form {
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
     * Returns the board and, when editing, the column the form works on.
     *
     * @return array{0: \stdClass, 1: \stdClass|null}
     */
    private function get_target(): array {
        $access = $this->get_access();
        $columnid = $this->optional_param('columnid', 0, PARAM_INT);
        if ($columnid > 0) {
            return $access->get_column($columnid);
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
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'boardid');
        $mform->setType('boardid', PARAM_INT);
        $mform->addElement('hidden', 'columnid');
        $mform->setType('columnid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('columntitle', 'mod_taskboard'), ['maxlength' => 255, 'size' => 30]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('text', 'wiplimit', get_string('wiplimit', 'mod_taskboard'), ['size' => 4]);
        $mform->setType('wiplimit', PARAM_INT);
        $mform->setDefault('wiplimit', 0);
        $mform->addHelpButton('wiplimit', 'wiplimit', 'mod_taskboard');

        $mform->addElement('advcheckbox', 'isdone', get_string('columnisdone', 'mod_taskboard'));
        $mform->addHelpButton('isdone', 'columnisdone', 'mod_taskboard');
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
        if (trim($data['title'] ?? '') === '') {
            $errors['title'] = get_string('required');
        }
        if ((int)($data['wiplimit'] ?? 0) < 0 || (int)($data['wiplimit'] ?? 0) > 99) {
            $errors['wiplimit'] = get_string('invalidwiplimit', 'mod_taskboard');
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
     * Checks the user may change the columns of the board.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        $access = $this->get_access();
        $access->require_view();
        [$board] = $this->get_target();
        if (!$access->can_manage_columns($board)) {
            throw new \moodle_exception('nopermissioncolumns', 'mod_taskboard');
        }
    }

    /**
     * Saves the column.
     *
     * @return array The id of the saved column.
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        [$board, $column] = $this->get_target();
        $service = new board_service($this->get_access());
        if ($column) {
            $service->update_column($column, (string)$data->title, (int)$data->wiplimit, !empty($data->isdone));
            return ['columnid' => (int)$column->id];
        }
        return ['columnid' => $service->add_column($board, (string)$data->title, (int)$data->wiplimit, !empty($data->isdone))];
    }

    /**
     * Loads the column into the form when editing.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        [$board, $column] = $this->get_target();
        $data = [
            'cmid' => $this->get_access()->cm->id,
            'boardid' => $board->id,
            'columnid' => $column ? $column->id : 0,
        ];
        if ($column) {
            $data += [
                'title' => $column->title,
                'wiplimit' => (int)$column->wiplimit,
                'isdone' => (int)$column->isdone,
            ];
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
