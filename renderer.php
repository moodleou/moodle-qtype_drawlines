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
 * Draw lines question renderer class.
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
 * Generates the output for draw lines questions.
 *
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines_renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Returns the URL for an image
     *
     * @param question_attempt $qa Question attempt object
     * @param string $filearea File area descriptor
     * @param int $itemid Item id to get
     * @return string Output url, or null if not found
     */
    protected static function get_url_for_image(question_attempt $qa, string $filearea, int $itemid = 0): ?string {
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
     * @param question_attempt $qa Question attempt object
     * @param string $varname The hidden var name
     * @param null|string $value The hidden value
     * @param null|array $classes Any additional css classes to apply
     * @return array Array with field name and the html of the tag
     */
    protected function hidden_field_for_qt_var(question_attempt $qa, string $varname, ?string $value = null,
            ?array $classes = null): array {
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
     *
     * @param question_attempt $qa Question attempt object
     * @param int $choicenumber choice number
     * @param null|string $value the hidden value
     * @return string the html of the choice
     */
    protected function hidden_field_choice(question_attempt $qa, int $choicenumber, ?string $value = null): string {
        $varname = $qa->get_question()->field($choicenumber);
        $classes = ['choices', 'choice'. $choicenumber];
        [, $html] = $this->hidden_field_for_qt_var($qa, $varname, $value, $classes);
        return $html;
    }

    #[\Override]
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
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
        $output .= html_writer::start_div($dropareaclass);
        $output .= html_writer::img(self::get_url_for_image($qa, 'bgimage'), get_string('dropbackground', 'qtype_drawlines'),
                ['class' => 'dropbackground img-fluid']);
        $output .= html_writer::start_div('que-dlines-dropzone');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

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
        $output = '';
        $output .= $this->combined_feedback($qa);
        $hint = $qa->get_applicable_hint();
        return $output;
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

    #[\Override]
    protected function hint(question_attempt $qa, question_hint $hint) {
        $output = '';
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $grademethod = $qa->get_question()->grademethod;
        // Accumulate the wrong coords for each lines to be displayed and hint options.
        $wrongcoords = [];
        if ($hint->showmisplaced) {
            foreach ($question->lines as $key => $line) {
                if (in_array($question->field($key), array_keys($response))) {
                    $coords = explode(' ', $response[$question->field($key)]);
                    if ($question->grademethod === 'partial') {
                        // Label the line.
                        $linelabelstart = null;
                        $linelabelend = null;
                        if (!line::is_dragitem_in_the_right_place($coords[0], $line->zonestart)) {
                            $linelabelstart = ' Line ' . $line->number;
                            $wrongcoords[] = html_writer::tag('span', $linelabelstart .
                                ' start(' . $coords[0] . ')', ['class' => 'misplaced']);
                        }
                        if (!line::is_dragitem_in_the_right_place($coords[1], $line->zoneend)) {
                            if (is_null($linelabelstart)) {
                                // Do not repeat the line number for the end-prt of the line coordinates.
                                $linelabelend = ' Line ' . $line->number;
                            }
                            $wrongcoords[] = html_writer::tag('span', $linelabelend .
                                ' end(' . $coords[1] . ')', ['class' => 'misplaced']);
                        }
                    } else {
                        if (!(line::is_dragitem_in_the_right_place($coords[0], $line->zonestart) &&
                            line::is_dragitem_in_the_right_place($coords[1], $line->zoneend))) {
                            $wrongcoords[] = html_writer::tag('span', ' Line ' . $line->number .
                                ' start(' . $coords[0] . ') end(' . $coords[1] . ')', ['class' => 'misplaced']);
                        }
                    }
                }
            }
            if (empty($wrongcoords)) {
                $output .= '';
            } else if (count($wrongcoords) === 1) {
                if ($grademethod === 'partial') {
                    $output .= html_writer::tag('div',
                        get_string('showmisplacedcoordinate', 'qtype_drawlines', implode($wrongcoords)),
                        ['class' => 'misplacedinfo']);
                } else {
                    $output .= html_writer::tag('div',
                        get_string('showmisplacedline', 'qtype_drawlines', implode($wrongcoords)),
                        ['class' => 'misplacedinfo']);
                }
            } else {
                if ($grademethod === 'partial') {
                    $output .= html_writer::tag('div',
                        get_string('showmisplacedcoordinates', 'qtype_drawlines', implode(',', $wrongcoords)),
                        ['class' => 'misplacedinfo']);
                } else {
                    $output .= html_writer::tag('div',
                        get_string('showmisplacedlines', 'qtype_drawlines', implode(',', $wrongcoords)),
                        ['class' => 'misplacedinfo']);
                }
            }
        }
        $output .= parent::hint($qa, $hint);
        return $output;
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
        $grademethod = $qa->get_question()->grademethod;
        if ($grademethod === 'partial') {
            [$a->num, $a->outof] = $qa->get_question()->get_num_parts_right_grade_partial($qa->get_last_qt_data());
        } else {
            [$a->num, $a->outof] = $qa->get_question()->get_num_parts_right_grade_allornone($qa->get_last_qt_data());
        }
        if ($a->num === 0 || is_null($a->outof)) {
            return '';
        }
        if ($a->num == 1) {
            if ($grademethod === 'partial') {
                return html_writer::tag('p', get_string('yougot1right', 'qtype_drawlines', $a));
            } else {
                return html_writer::tag('p', get_string('yougot1rightline', 'qtype_drawlines', $a));
            }
        } else {
            $f = new NumberFormatter(current_language(), NumberFormatter::SPELLOUT);
            $a->num = $f->format($a->num);
            if ($grademethod === 'partial') {
                return html_writer::tag('p', get_string('yougotnright', 'qtype_drawlines', $a));
            } else {
                return html_writer::tag('p', get_string('yougotnrightline', 'qtype_drawlines', $a));
            }
        }
    }
}
