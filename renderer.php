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

/**
 * DrawLines question renderer class.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @author    The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_drawlines\line;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/rendererbase.php');


/**
 * Generates the output for DrawLines questions.
 *
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines_renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Returns the URL for an image
     *
     * @param object $qa Question attempt object
     * @param string $filearea File area descriptor
     * @param int $itemid Item id to get
     * @return string Output url, or null if not found
     */
    protected static function get_url_for_image(question_attempt $qa, $filearea, $itemid = 0) {
        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        if ($filearea == 'bgimage') {
            $itemid = $question->id;
        }
        $componentname = $question->qtype->plugin_name();
        $draftfiles = $fs->get_area_files($question->contextid, $componentname,
                $filearea, $itemid, 'id');
        if ($draftfiles) {
            foreach ($draftfiles as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $url = moodle_url::make_pluginfile_url($question->contextid, $componentname,
                        $filearea, "$qubaid/$slot/{$itemid}", '/',
                        $file->get_filename());
                return $url->out();
            }
        }
        return null;
    }

    /**
     * Returns a hidden field for a qt variable
     *
     * @param object $qa Question attempt object
     * @param string $varname The hidden var name
     * @param string $value The hidden value
     * @param array $classes Any additional css classes to apply
     * @return array Array with field name and the html of the tag
     */
    protected function hidden_field_for_qt_var(question_attempt $qa, $varname, $value = null, $classes = null) {
        if ($value === null) {
            $value = $qa->get_last_qt_var($varname);
        }
        $fieldname = $qa->get_qt_field_name($varname);
        $attributes = ['type' => 'hidden',
                'id' => str_replace(':', '_', $fieldname),
                'name' => $fieldname,
                'value' => $value];
        if ($classes !== null) {
            $attributes['class'] = join(' ', $classes);
        }
        return [$fieldname, html_writer::empty_tag('input', $attributes)."\n"];
    }

    /**
     * Generate the hidden fields on the preview page to capture the responses.
     * @param question_attempt $qa
     * @param $choicenumber
     * @param $value
     * @param $class
     * @return mixed
     */
    protected function hidden_field_choice(question_attempt $qa, $choicenumber, $value = null, $class = null) {
        $varname = 'c'. $choicenumber;
        $classes = ['choices', 'choice'. $choicenumber];
        [, $html] = $this->hidden_field_for_qt_var($qa, $varname, $value, $classes);
        return $html;
    }

    #[\Override]
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $choicesorder = $qa->get_qt_field_name('choicesorder');
        $visibilityzones = $response;
        $componentname = $question->qtype->plugin_name();

        $questiontext = $question->format_questiontext($qa);

        $dropareaclass = 'droparea';
        $draghomesclass = 'draghomes';
        if ($options->readonly) {
            $dropareaclass .= ' readonly';
            $draghomesclass .= ' readonly';
        }

        $output = html_writer::div($questiontext, 'qtext');

        $output .= html_writer::start_div('ddarea');
        $output .= html_writer::start_div($dropareaclass, ['id' => 'que-dlines-droparea']);
        $output .= html_writer::img(self::get_url_for_image($qa, 'bgimage'), get_string('dropbackground', 'qtype_drawlines'),
                ['class' => 'dropbackground img-fluid']);
        $output .= html_writer::start_div('', ['id' => 'que-dlines-dropzone']);
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        $output .= html_writer::div('', 'markertexts');

        $output .= html_writer::start_div($draghomesclass);

        if (!$options->readonly) {
            $attr['tabindex'] = 0;
        }

        if ($qa->get_state() == question_state::$invalid) {
            $output .= html_writer::div($question->get_validation_error($qa->get_last_qt_data()), 'validationerror');
        }
        $output .= html_writer::end_div();

        $hiddenfields = '';
        foreach ($question->lines as $line) {
            $hiddenfields .= $this->hidden_field_choice($qa, $line->number - 1);
        }
        $output .= html_writer::div($hiddenfields, 'dragchoices');

        // Call to js.
        $this->page->requires->js_call_amd('qtype_drawlines/question', 'init',
                [$qa->get_outer_question_div_unique_id(), $options->readonly, $visibilityzones, $question->lines]);

        $output .= html_writer::end_div();

        return $output;
    }

    #[\Override]
    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    #[\Override]
    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $rightanswers = [];
        $lineindicator = '';
        foreach ($question->lines as $line) {
            switch ($line->type) {
                case 'linesinglearrow':
                    $lineindicator = ' &xrarr; ';
                    break;
                case 'linedoublearrows':
                    $lineindicator = ' &xharr; ';
                    break;
                case 'linesegment':
                    $lineindicator = ' --- ';
                    break;
                case 'lineinfinite':
                    $lineindicator = ' --o--o-- ';
                    break;
            }
            $rightanswers[] = "Line " . $line->number . ': ' . line::get_coordinates($line->zonestart)
                    . $lineindicator . line::get_coordinates($line->zoneend);
        }
        return $this->correct_choices($rightanswers);
    }

    /**
     * Function returns string based on number of correct answers.
     *
     * @param array $right An Array of correct responses to the current question
     * @return string based on number of correct responses
     */
    protected function correct_choices(array $right): string {
        // Return appropriate string for single/multiple correct answer(s).
        $correctanswers = "<br>" . implode("<br>", $right);
        if (count($right) == 1) {
            return get_string('correctansweris', 'qtype_drawlines', $correctanswers);
        } else if (count($right) > 2) {
            return get_string('correctanswersare', 'qtype_drawlines', $correctanswers);
        } else {
            return "";
        }
    }

    #[\Override]
    protected function num_parts_correct(question_attempt $qa): string {
        $a = new stdClass();
        list($a->num, $a->outof) = $qa->get_question()->get_num_parts_right(
                $qa->get_last_qt_data());
        if (is_null($a->outof)) {
            return '';
        } else if ($a->num == 1) {
            return html_writer::tag('p', get_string('yougot1right', 'qtype_drawlines'));
        } else {
            $f = new NumberFormatter(current_language(), NumberFormatter::SPELLOUT);
            $a->num = $f->format($a->num);
            return html_writer::tag('p', get_string('yougotnright', 'qtype_drawlines', $a));
        }
    }
}
