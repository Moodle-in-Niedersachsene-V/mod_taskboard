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
 * Settings form for a task board activity.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_taskboard\local\presets;

/**
 * Settings form for a task board activity.
 *
 * @package    mod_taskboard
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_taskboard_mod_form extends moodleform_mod {
    /**
     * Defines the form fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'boardsettings', get_string('boardsettings', 'mod_taskboard'));
        $mform->setExpanded('boardsettings');

        $mform->addElement('select', 'boardtype', get_string('boardtype', 'mod_taskboard'), [
            presets::BOARDTYPE_COURSE => get_string('boardtype_course', 'mod_taskboard'),
            presets::BOARDTYPE_GROUP => get_string('boardtype_group', 'mod_taskboard'),
            presets::BOARDTYPE_PERSONAL => get_string('boardtype_personal', 'mod_taskboard'),
        ]);
        $mform->addHelpButton('boardtype', 'boardtype', 'mod_taskboard');

        $mform->addElement(
            'select',
            'columntemplate',
            get_string('columntemplate', 'mod_taskboard'),
            presets::column_template_options()
        );
        $mform->setDefault('columntemplate', 'ideas');
        $mform->addHelpButton('columntemplate', 'columntemplate', 'mod_taskboard');

        $mform->addElement('select', 'cardsize', get_string('cardsize', 'mod_taskboard'), presets::cardsize_options());
        $mform->setDefault('cardsize', get_config('mod_taskboard', 'defaultcardsize') ?: 'm');
        $mform->addHelpButton('cardsize', 'cardsize', 'mod_taskboard');

        $mform->addElement('advcheckbox', 'allowusersize', get_string('allowusersize', 'mod_taskboard'));
        $mform->setDefault('allowusersize', 1);
        $mform->addHelpButton('allowusersize', 'allowusersize', 'mod_taskboard');

        $mform->addElement('text', 'wiplimit', get_string('wiplimit', 'mod_taskboard'), ['size' => 4]);
        $mform->setType('wiplimit', PARAM_INT);
        $mform->setDefault('wiplimit', 3);
        $mform->addHelpButton('wiplimit', 'wiplimit', 'mod_taskboard');

        $mform->addElement('header', 'rightssettings', get_string('rightssettings', 'mod_taskboard'));
        $mform->addElement('select', 'studentrights', get_string('studentrights', 'mod_taskboard'), [
            presets::RIGHTS_MOVE => get_string('studentrights_move', 'mod_taskboard'),
            presets::RIGHTS_OWN => get_string('studentrights_own', 'mod_taskboard'),
            presets::RIGHTS_ALL => get_string('studentrights_all', 'mod_taskboard'),
        ]);
        $mform->setDefault('studentrights', presets::RIGHTS_OWN);
        $mform->addHelpButton('studentrights', 'studentrights', 'mod_taskboard');

        $mform->addElement('advcheckbox', 'approval', get_string('approval', 'mod_taskboard'));
        $mform->addHelpButton('approval', 'approval', 'mod_taskboard');
        $mform->hideIf('approval', 'studentrights', 'eq', presets::RIGHTS_MOVE);

        $mform->addElement('header', 'mediasettings', get_string('mediasettings', 'mod_taskboard'));
        $mform->addElement('advcheckbox', 'allowimages', get_string('allowimages', 'mod_taskboard'));
        $mform->setDefault('allowimages', 1);
        $mform->addHelpButton('allowimages', 'allowimages', 'mod_taskboard');

        $mform->addElement('advcheckbox', 'requirealt', get_string('requirealt', 'mod_taskboard'));
        $mform->setDefault('requirealt', 1);
        $mform->addHelpButton('requirealt', 'requirealt', 'mod_taskboard');
        $mform->hideIf('requirealt', 'allowimages', 'notchecked');

        $mform->addElement('advcheckbox', 'allowvideos', get_string('allowvideos', 'mod_taskboard'));
        $mform->addHelpButton('allowvideos', 'allowvideos', 'mod_taskboard');
        if (!\mod_taskboard\local\video_embed::allowed_hosts()) {
            $mform->freeze('allowvideos');
        }

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds the custom completion rule: a number of the user's cards in done columns.
     *
     * @return array Names of the added elements.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $enabledel = 'completioncardsenabled' . $suffix;
        $valueel = 'completioncards' . $suffix;
        $groupel = 'completioncardsgroup' . $suffix;
        $group = [];
        $group[] = $mform->createElement('checkbox', $enabledel, '', get_string('completioncards', 'mod_taskboard'));
        $group[] = $mform->createElement('text', $valueel, '', ['size' => 3]);
        $mform->setType($valueel, PARAM_INT);
        $mform->addGroup($group, $groupel, '', ' ', false);
        $mform->addHelpButton($groupel, 'completioncards', 'mod_taskboard');
        $mform->hideIf($valueel, $enabledel, 'notchecked');
        return [$groupel];
    }

    /**
     * Whether the custom completion rule is enabled.
     *
     * @param array $data The submitted values.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['completioncardsenabled' . $suffix]) && (int)($data['completioncards' . $suffix] ?? 0) > 0;
    }

    /**
     * Prepares stored values for the form.
     *
     * @param array $defaultvalues The values, changed in place.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        $suffix = $this->get_suffix();
        $defaultvalues['completioncardsenabled' . $suffix] = !empty($defaultvalues['completioncards' . $suffix]) ? 1 : 0;
        if (empty($defaultvalues['completioncards' . $suffix])) {
            $defaultvalues['completioncards' . $suffix] = 1;
        }
    }

    /**
     * Turns the completion rule off when its checkbox is not ticked.
     *
     * @param stdClass $data The submitted values, changed in place.
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? 0;
            $automatic = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{'completioncardsenabled' . $suffix}) || !$automatic) {
                $data->{'completioncards' . $suffix} = 0;
            }
        }
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
        if ((int)($data['wiplimit'] ?? 0) < 0 || (int)($data['wiplimit'] ?? 0) > 99) {
            $errors['wiplimit'] = get_string('invalidwiplimit', 'mod_taskboard');
        }
        return $errors;
    }
}
