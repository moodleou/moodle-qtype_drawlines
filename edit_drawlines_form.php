<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use qtype_drawlines\line;

/**
 * Defines the editing form for DrawLines question type.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @author    The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines_edit_form extends question_edit_form {

    /** @var int Number of lines. */
    protected $numberoflines;

    /** @var string gardemethod of rows. */
    protected $grademethod;

    public function qtype(): string {
        return 'drawlines';
    }

    /**
     * Set basic the number of lines.
     *
     * @return void
     */
    protected function set_current_settings(): void {
        $grademethod = optional_param('grademethod', '', PARAM_ALPHA);
        if ($grademethod == '') {
            $grademethod = $this->question->options->grademethod ?? $this->get_default_value('grademethod',
                            get_config('qtype_' . $this->qtype(), 'grademethod'));
        }
        $this->grademethod = $grademethod;
        if (isset($this->question->lines)) {
            $this->numberoflines = count($this->question->lines);
        } else {
            $this->numberoflines = line::LINE_NUMBER_START;
        }
    }

    /**
     * Options shared by all file pickers in the form.
     *
     * @return array Array of filepicker options.
     */
    public static function file_picker_options() {
        $filepickeroptions = [];
        $filepickeroptions['accepted_types'] = ['web_image'];
        $filepickeroptions['maxbytes'] = 0;
        $filepickeroptions['maxfiles'] = 1;
        $filepickeroptions['subdirs'] = 0;
        return $filepickeroptions;
    }

    protected function definition_inner($mform) {

        $this->set_current_settings();

        $grademethodmenu = [
                'partial' => get_string('gradepartialcredit', 'qtype_' . $this->qtype()),
                'allnone' => get_string('gradeallornothing', 'qtype_drawlines'),
        ];
        $mform->addElement('select', 'grademethod',
                get_string('grademethod', 'qtype_' . $this->qtype()), $grademethodmenu);
        $mform->addHelpButton('grademethod', 'grademethod', 'qtype_drawlines');
        $mform->setDefault('grademethod', $this->get_default_value('grademethod',
                get_config('qtype_drawlines', 'grademethod')));

        // Preview section.
        $mform->addElement('header', 'previewareaheader',
                get_string('previewareaheader', 'qtype_' . $this->qtype()));
        $mform->setExpanded('previewareaheader');
        $mform->addElement('static', 'previewarea', '',
                get_string('previewareamessage', 'qtype_' . $this->qtype()));

        $mform->registerNoSubmitButton('refresh');
        $mform->addElement('submit', 'refresh', get_string('refresh', 'qtype_' . $this->qtype()));
        $mform->addElement('filepicker', 'bgimage', get_string('bgimage', 'qtype_' . $this->qtype()),
                null, self::file_picker_options());

        $this->add_per_line_fields($mform, get_string('linexheader', 'qtype_' . $this->qtype(), '{no}'));

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $mform = $this->_form;

        $repeated = [];
        $repeated[] = $mform->createElement('editor', 'hint',
                get_string('hintn', 'question'), ['rows' => 5], $this->editoroptions);
        $repeatedoptions['hint']['type'] = PARAM_RAW;

        $repeated[] = $mform->createElement('checkbox', 'hintshownumcorrect',
                get_string('options', 'question'),
                get_string('shownumpartscorrect', 'question'));
        $repeated[] = $mform->createElement('checkbox', 'hintoptions', '',
                get_string('showmisplaced', 'qtype_' . $this->qtype()));

        return [$repeated, $repeatedoptions];
    }

    public function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_options($question);
        $question = $this->data_preprocessing_lines($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        // Initialise file picker for bgimage.
        $draftitemid = file_get_submitted_draft_itemid('bgimage');

        file_prepare_draft_area($draftitemid, $this->context->id, 'qtype_' . $this->qtype(),
                'bgimage', !empty($question->id) ? (int) $question->id : null,
                self::file_picker_options());
        $question->bgimage = $draftitemid;

        // TODO: Require js amd module for fom.

        return $question;
    }

    /**
     * Perform the necessary preprocessing for the options fields.
     *
     * @param stdClass $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_options(stdClass $question): stdClass {
        if (empty($question->options)) {
            return $question;
        }
        $question->options->grademethod = $question->options->grademethod ?? get_config('qtype_drawlines', 'grademethod');
        $question->options->shownumcorrect = $question->options->shownumcorrect ?? 0;
        $question->options->showmisplaced = $question->options->showmisplaced ?? 0;
        return $question;
    }

    /**
     * Perform the necessary preprocessing for rows (sub-questions) fields.
     *
     * @param stdClass $question The data being passed to the form.
     * @return object The modified data.
     */
    protected function data_preprocessing_lines(stdClass $question): object {
        if (empty($question->lines)) {
            return $question;
        }
        $lineindex = 0;
        foreach ($question->lines as $line) {
            // If the line type has not been set correctly is assumed that the line is empty.
            if (!in_array($line->type, array_keys(line::get_line_types()))) {
                continue;
            }
            $question->type[$lineindex] = $line->type;
            $question->labelstart[$lineindex] = $line->labelstart;
            $question->labelmiddle[$lineindex] = $line->labelmiddle;
            $question->labelend[$lineindex] = $line->labelend;
            $question->zonestart[$lineindex] = $line->zonestart;
            $question->zoneend[$lineindex] = $line->zoneend;
            $lineindex++;
        }
        return $question;
    }

    /**
     * Perform the necessary preprocessing for the hint fields.
     *
     * @param object $question The data being passed to the form.
     * @param bool $withclearwrong Clear wrong hints.
     * @param bool $withshownumpartscorrect Show number correct.
     * @return object The modified data.
     */
    protected function data_preprocessing_hints($question, $withclearwrong = false,
            $withshownumpartscorrect = false) {
        if (empty($question->hints)) {
            return $question;
        }
        parent::data_preprocessing_hints($question, $withclearwrong, $withshownumpartscorrect);

        $question->hintoptions = [];
        foreach ($question->hints as $hint) {
            $question->hintoptions[] = $hint->options;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $bgimagesize = $this->get_image_size_in_draft_area($data['bgimage']);
        if (!$bgimagesize === null) {
            $errors["bgimage"] = get_string('formerror_nobgimage', 'qtype_' . $this->qtype());
        }
        // Validate whether the line type error needed to be displayed.
        for ($i = 0; $i < $this->numberoflines; $i++) {
            // Validate line type.
            if (!in_array($data["type"][$i], array_keys(line::get_line_types())) &&
                    trim($data["zonestart"][$i]) != '' && trim($data["zoneend"][$i]) != '') {
                $errors["type[$i]"] = get_string('formerror_notype', 'qtype_' . $this->qtype(), $i + 1);
            }

            if (in_array($data["type"][$i], array_keys(line::get_line_types())) &&
                    !line::is_zone_coordinates_valid($data["zonestart"][$i])) {
                $errors["zonestart[$i]"] = get_string('formerror_zonestart', 'qtype_drawlines');
            }
            if (in_array($data["type"][$i], array_keys(line::get_line_types())) &&
                    !line::is_zone_coordinates_valid($data['zoneend'][$i])) {
                $errors["zoneend[$i]"] = get_string('formerror_zoneend', 'qtype_drawlines');
            }
        }
        return $errors;
    }

    /**
     * Gets the width and height of a draft image.
     *
     * @param int $draftitemid ID of the draft image
     * @return array Return array of the width and height of the draft image.
     */
    public function get_image_size_in_draft_area($draftitemid) {
        global $USER;
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id');
        if ($draftfiles) {
            foreach ($draftfiles as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                // Just return the data for the first good file, there should only be one.
                $imageinfo = $file->get_imageinfo();
                $width = $imageinfo['width'];
                $height = $imageinfo['height'];
                return array($width, $height);
            }
        }
        return null;
    }

    /**
     * Add a set of form fields, obtained from get_per_line_fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     * @param string $label the label to use for each line.
     */
    protected function add_per_line_fields(MoodleQuickForm $mform, string $label) {
        $repeatsatstart = line::LINE_NUMBER_START;
        if (isset($this->question->lines)) {
            $repeatsatstart = count((array)$this->question->lines);
        }
        $repeatedoptions = [];
        $this->repeat_elements($this->get_per_line_fields($mform, $label, $repeatedoptions),
                $repeatsatstart, $repeatedoptions,
                'numberoflines', 'addlines', line::LINE_NUMBER_ADD,
                get_string('addmoreblanks', 'qtype_drawlines', 'lines'), true);
    }

    /**
     * Returns a line object with relevant input fields.
     *
     * @param MoodleQuickForm $mform
     * @param string $label
     * @param array $repeatedoptions
     * @return array
     */
    protected function get_per_line_fields(MoodleQuickForm $mform, string $label, array &$repeatedoptions): array {

        $repeated = [];

        $repeated[] = $mform->createElement('header', 'linexheader', $label);
        $mform->setType('linexheader', PARAM_RAW);

        $repeated[] = $mform->createElement('select', 'type',
                get_string('type', 'qtype_' . $this->qtype()),
                array_merge(['choose' => get_string('choose')], line::get_line_types()));
        $mform->setType('type', PARAM_RAW);

        $repeated[] = $mform->createElement('text', 'labelstart',
                get_string('labelstart', 'qtype_' . $this->qtype()), ['size' => 20]);
        $mform->setType('labelstart', PARAM_RAW_TRIMMED);

        $repeated[] = $mform->createElement('text', 'labelmiddle',
                get_string('labelmiddle', 'qtype_' . $this->qtype()), ['size' => 20]);
        $mform->setType('labelmiddle', PARAM_RAW_TRIMMED);

        $repeated[] = $mform->createElement('text', 'labelend',
                get_string('labelend', 'qtype_' . $this->qtype()), ['size' => 20]);
        $mform->setType('labelend', PARAM_RAW_TRIMMED);

        $repeated[] = $mform->createElement('text', 'zonestart',
                get_string('zonestart', 'qtype_' . $this->qtype()), ['size' => 20]);
        $mform->setType('zonestart', PARAM_RAW_TRIMMED);

        $repeated[] = $mform->createElement('text', 'zoneend',
                get_string('zoneend', 'qtype_' . $this->qtype()), ['size' => 20]);
        $mform->setType('zoneend', PARAM_RAW_TRIMMED);
        return $repeated;
    }
}
