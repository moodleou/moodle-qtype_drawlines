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
 * Draw lines question qtype_drawlines class.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @author    The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_drawlines\line;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/questionlib.php');

/**
 * The Draw lines question type class.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines extends question_type {

    #[\Override]
    public function get_question_options($question): bool {
        global $DB, $OUTPUT;
        parent::get_question_options($question);
        if (!$question->options = $DB->get_record('qtype_drawlines_options', ['questionid' => $question->id])) {
            echo $OUTPUT->notification('Error: Missing draw lines question options!');
            return false;
        }
        if (!$question->lines = $DB->get_records('qtype_drawlines_lines', ['questionid' => $question->id], 'id')) {
            echo $OUTPUT->notification('Error: Missing draw lines question lines!');
            return false;
        }
        return true;
    }

    #[\Override]
    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('grademethod', $fromform->grademethod);
        $this->set_default_value('shownumcorrect', $fromform->shownumcorrect);
        $this->set_default_value('showmisplaced', $fromform->showmisplaced);
    }

    #[\Override]
    public function save_question_options($fromform) {
        global $DB;
        parent::save_question_options($fromform);
        $context = $fromform->context;
        $options = $DB->get_record('qtype_drawlines_options', ['questionid' => $fromform->id]);

        file_save_draft_area_files($fromform->bgimage, $fromform->context->id,
                'qtype_drawlines', 'bgimage', $fromform->id,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]);

        if (!$options) {
            $options = new stdClass();
            $options->questionid = $fromform->id;
        }
        $options->grademethod = $fromform->grademethod ?? get_config('qtype_drawlines', 'grademethod');
        $options->shownumcorrect = !empty($formdata->shownumcorrect) ? 1 : 0;
        $options->showmisplaced = !empty($formdata->showmisplaced);
        $options = $this->save_combined_feedback_helper($options, $fromform, $context, true);
        $DB->insert_record('qtype_drawlines_options', $options);

        $this->save_lines($fromform);
        $this->save_hints($fromform, true);
    }

    /**
     * Save the question lines.
     *
     * @param stdClass $fromform This holds the information from the editing form
     * @return array, array of lines
     */
    public function save_lines(stdClass $fromform): void {
        global $DB;
        $numberoflines = $fromform->numberoflines;
        $index = 1;
        for ($i = 0; $i < $numberoflines; $i++) {
            // If line type is not set do not save the line object.
            if (!in_array($fromform->type[$i], array_keys(line::get_line_types()))) {
                continue;
            }
            $line = new stdClass;
            $line->questionid = $fromform->id;
            $line->number = $index++;
            $line->type = $fromform->type[$i];
            $line->labelstart = $fromform->labelstart[$i];
            $line->labelmiddle = $fromform->labelmiddle[$i];
            $line->labelend = $fromform->labelend[$i];
            $line->zonestart = $fromform->zonestart[$i];
            $line->zoneend = $fromform->zoneend[$i];
            $line->id = $DB->insert_record('qtype_drawlines_lines', $line);
        }
    }

    #[\Override]
    public function save_hints($fromform, $withparts = false) {
        global $DB;
        $context = $fromform->context;

        $oldhints = $DB->get_records('question_hints', ['questionid' => $fromform->id], 'id ASC');

        if (!empty($fromform->hint)) {
            $numhints = max(array_keys($fromform->hint)) + 1;
        } else {
            $numhints = 0;
        }

        if ($withparts) {
            if (!empty($fromform->hintshownumcorrect)) {
                $numshows = max(array_keys($fromform->hintshownumcorrect)) + 1;
            } else {
                $numshows = 0;
            }
            if (!empty($fromform->hintshowmisplaced)) {
                $nummisplaced = max(array_keys($fromform->hintshowmisplaced)) + 1;
            } else {
                $nummisplaced = 0;
            }
            $numhints = max($numhints, $nummisplaced, $numshows);
        }
        for ($i = 0; $i < $numhints; $i += 1) {
            if (html_is_blank($fromform->hint[$i]['text'])) {
                $fromform->hint[$i]['text'] = '';
            }

            if ($withparts) {
                $shownumcorrect = !empty($fromform->hintshownumcorrect[$i]);
                $showmisplaced = !empty($fromform->hintshowmisplaced[$i]);
            }
            if (empty($fromform->hint[$i]['text']) && empty($shownumcorrect) && empty($showmisplaced)) {
                continue;
            }

            // Update an existing hint if possible.
            $hint = array_shift($oldhints);
            if (!$hint) {
                $hint = new stdClass();
                $hint->questionid = $fromform->id;
                $hint->hint = '';
                $hint->id = $DB->insert_record('question_hints', $hint);
            }

            $hint->hint = $this->import_or_save_files($fromform->hint[$i],
                    $context, 'question', 'hint', $hint->id);
            $hint->hintformat = $fromform->hint[$i]['format'];
            if ($withparts) {
                $hint->shownumcorrect = $shownumcorrect;
                $hint->options = $showmisplaced;
            }
            $DB->update_record('question_hints', $hint);
        }

        // Delete any remaining old hints.
        $fs = get_file_storage();
        foreach ($oldhints as $oldhint) {
            $fs->delete_area_files($context->id, 'question', 'hint', $oldhint->id);
            $DB->delete_records('question_hints', ['id' => $oldhint->id]);
        }
    }

    #[\Override]
    protected function initialise_question_instance(question_definition $question, $questiondata): void {
        parent::initialise_question_instance($question, $questiondata);
        $question->grademethod = $questiondata->options->grademethod;
        $this->initialise_question_lines($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata, true);
    }

    /**
     * Initialise the lines for this question.
     *
     * @param question_definition $question
     * @param qtype_drawlines_question $questiondata
     */
    protected function initialise_question_lines(question_definition $question, $questiondata): void {
        $question->numberoflines = count($questiondata->lines);
        foreach ($questiondata->lines as $line) {
            $question->lines[$line->number - 1] = $this->make_line($line);
        }
    }

    #[\Override]
    protected function initialise_combined_feedback(question_definition $question, $questiondata, $withparts = false) {
        parent::initialise_combined_feedback($question, $questiondata, $withparts);
        $question->showmisplaced = $questiondata->options->showmisplaced ?? 0;
    }

    /**
     * Make a line.
     *
     * @param stdClass $line
     * @return data
     */
    public function make_line(stdClass $line): line {
        return new line($line->id, $line->questionid, $line->number, $line->type,
                $line->labelstart, $line->labelmiddle, $line->labelend,
                $line->zonestart, $line->zoneend);
    }

    #[\Override]
    protected function make_hint($hint) {
        return question_hint_drawlines::load_from_record($hint);
    }

    #[\Override]
    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_drawlines_options', ['questionid' => $questionid]);
        $DB->delete_records('qtype_drawlines_lines', ['questionid' => $questionid]);
        parent::delete_question($questionid, $contextid);
    }

    #[\Override]
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        global $DB;
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs->move_area_files_to_new_context($oldcontextid,
                                    $newcontextid, 'qtype_drawlines', 'bgimage', $questionid);

        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    /**
     * Delete all the files belonging to this question.
     * @param int $questionid the question being deleted.
     * @param int $contextid the context the question is in.
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
        $fs->delete_area_files($contextid, 'question', 'correctfeedback', $questionid);
        $fs->delete_area_files($contextid, 'question', 'partiallycorrectfeedback', $questionid);
        $fs->delete_area_files($contextid, 'question', 'incorrectfeedback', $questionid);
    }

    #[\Override]
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';
        $output .= '    <grademethod>' . $format->xml_escape($question->options->grademethod) .
                "</grademethod>\n";
        $output .= $format->write_combined_feedback($question->options,
                                                    $question->id,
                                                    $question->contextid);
        $fs = get_file_storage();
        $contextid = $question->contextid;
        $files = $fs->get_area_files($contextid, 'qtype_drawlines', 'bgimage', $question->id);
        $output .= "    " . $this->write_files($files, 2)."\n";;

        // Export lines data.
        $indent = 4;
        $output .= "    <lines>\n";
        foreach ($question->lines as $key => $line) {
            $output .= "    <line number=\"{$line->number}\">\n";
            $output .= "        <type>\n";
            $output .= $format->writetext($line->type, $indent);
            $output .= "        </type>\n";
            $output .= "        <labelstart>\n";
            $output .= $format->writetext($line->labelstart, $indent);
            $output .= "        </labelstart>\n";
            $output .= "        <labelmiddle>\n";
            $output .= $format->writetext($line->labelmiddle, $indent);
            $output .= "        </labelmiddle>\n";
            $output .= "        <labelend>\n";
            $output .= $format->writetext($line->labelend, $indent);
            $output .= "        </labelend>\n";
            $output .= "        <zonestart>\n";
            $output .= $format->writetext($line->zonestart, $indent);
            $output .= "        </zonestart>\n";
            $output .= "        <zoneend>\n";
            $output .= $format->writetext($line->zoneend, $indent);
            $output .= "        </zoneend>\n";
            $output .= "      </line>\n";
        }
        $output .= "    </lines>\n";

        return $output;
    }

    #[\Override]
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'drawlines') {
            return false;
        }
        $question = $format->import_headers($data);
        $question->qtype = 'drawlines';

        $filexml = $format->getpath($data, ['#', 'file'], []);
        $question->bgimage = $format->import_files_as_draft($filexml);

        $question->grademethod = $format->import_text(
                $format->getpath($data, ['#', 'grademethod'], 'partial'));
        $question->showmisplaced = array_key_exists('showmisplaced',
                $format->getpath($data, ['#'], []));

        $lines = $format->getpath($data, ['#', 'lines', 0, '#', 'line'], false);
        if ($lines) {
            $question->numberoflines = count($lines);
            $index = 0;
            foreach ($lines as $line) {
                $question->type[$index] = $format->getpath($line,
                        ['#', 'type', 0, '#', 'text', 0, '#'], line::TYPE_LINE_SEGMENT);
                $question->labelstart[$index] = $format->getpath($line,
                        ['#', 'labelstart', 0, '#', 'text', 0, '#'],  '');
                $question->labelmiddle[$index] = $format->getpath($line,
                        ['#', 'labelmiddle', 0, '#', 'text', 0, '#'],  '');
                $question->labelend[$index] = $format->getpath($line,
                        ['#', 'labelend', 0, '#', 'text', 0, '#'],  '');
                $question->zonestart[$index] = $format->getpath($line,
                        ['#', 'zonestart', 0, '#', 'text', 0, '#'],  '');
                $question->zoneend[$index] = $format->getpath($line,
                        ['#', 'zoneend', 0, '#', 'text', 0, '#'],  '');
                $index++;
            }
        }

        $format->import_combined_feedback($question, $data, true);

        $format->import_hints($question, $data, true, true,
                $format->get_format($question->questiontextformat));
        $question->hintshowmisplaced = $question->hintoptions;
        return $question;
    }

    /**
     * Convert files into text output in the given format.
     * This method is copied from qformat_default as a quick fix, as the method there is protected.
     * @param array $files
     * @param int $indent Number of spaces to indent
     * @return string $string
     */
    public function write_files($files, $indent) {
        if (empty($files)) {
            return '';
        }
        $string = '';
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $string .= str_repeat('  ', $indent);
            $string .= '<file name="' . $file->get_filename() . '" encoding="base64">';
            $string .= base64_encode($file->get_content());
            $string .= "</file>\n";
        }
        return $string;
    }

    #[\Override]
    public function get_random_guess_score($questiondata) {
        return 0;
    }

    #[\Override]
    public function get_possible_responses($questiondata) {
        if ($questiondata->options->grademethod == 'partial') {
            $partialcredit = 0.5;
        } else {
            $partialcredit = 0;
        }
        $question = $this->make_question($questiondata);
        $parts = [];
        foreach ($question->lines as $lineno => $place) {
            $coordsstart = explode(';', $question->lines[$lineno]->zonestart);
            $coordsend = explode(';', $question->lines[$lineno]->zoneend);
            $parts['Line ' . $place->number . ' (' . $coordsstart[0] . ') (' . $coordsend[0] . ')'] = [
                1 => new question_possible_response(get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1),
            ];
            $parts['Line ' . $place->number . ' (' .$coordsstart[0] . ')'] = [
                2 => new question_possible_response(get_string('valid_startcoordinates', 'qtype_drawlines'), $partialcredit),
            ];
            $parts['Line ' . $place->number . ' (' .$coordsend[0] . ')'] = [
                3 => new question_possible_response(get_string('valid_endcoordinates', 'qtype_drawlines'), $partialcredit),
            ];
            $parts['Line ' . $place->number] = [
                4 => new question_possible_response(get_string('incorrectresponse', 'qtype_drawlines'), 0),
                null => question_possible_response::no_response(),
            ];
        }
        return $parts;
    }
}

/**
 * Question hint for drawlines.
 * An extension of {@link question_hint} for questions like match and multiple
 * choice with multiple answers, where there are options for whether to show the
 * number of parts right at each stage, and to reset the wrong parts.
 *
 * @package   qtype_drawlines
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_hint_drawlines extends question_hint_with_parts {

    /** @var bool option to display the parts of the question that were wrong on retry.*/
    public $showmisplaced;

    /**
     * Constructor.
     * @param int the hint id from the database.
     * @param string $hint The hint text
     * @param int the corresponding text FORMAT_... type.
     * @param bool $shownumcorrect whether the number of right parts should be shown
     * @param bool $clearwrong whether the wrong parts should be reset.
     * @param bool $showmisplaced whether the show the wrong parts.
     */
    public function __construct($id, $hint, $hintformat, $shownumcorrect,
            $clearwrong, $showmisplaced) {
        parent::__construct($id, $hint, $hintformat, $shownumcorrect, $clearwrong);
        $this->showmisplaced = $showmisplaced;
    }

    /**
     * Create a basic hint from a row loaded from the question_hints table in the database.
     * @param object $row with property options as well as hint, shownumcorrect and clearwrong set.
     * @return question_hint_drawlines
     */
    public static function load_from_record($row) {
        return new question_hint_drawlines($row->id, $row->hint, $row->hintformat,
                $row->shownumcorrect, $row->clearwrong, $row->options);
    }
}
