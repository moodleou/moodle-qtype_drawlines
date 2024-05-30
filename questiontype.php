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
 * The Draw lines question type class.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @author    The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines extends question_type {

    /** @var lines[], an array of line objects. */
    public $lines;

    public function get_question_options($question): bool|stdClass {
        global $DB, $OUTPUT;
        parent::get_question_options($question);
        if (!$question->options = $DB->get_record('qtype_drawlines_options', ['questionid' => $question->id])) {
            echo $OUTPUT->notification('Error: Missing drawlines question options!');
            return false;
        }
        if (!$question->lines = $DB->get_records('qtype_drawlines_lines', ['questionid' => $question->id], 'id')) {
            echo $OUTPUT->notification('Error: Missing drawlines question lines!');
            return false;
        }
        return $question;
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('grademethod', $fromform->grademethod);
        $this->set_default_value('shownumcorrect', $fromform->shownumcorrect);
    }

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
        $options->grademethod = $fromform->grademethod;
        $options->shownumcorrect = !empty($formdata->shownumcorrect);
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
        for ($i = 0; $i < $numberoflines; $i++) {
            // If line type is not set do not save the line object.
            if (!in_array($fromform->type[$i], array_keys(line::get_line_types()))) {
                continue;
            }
            $line = new stdClass;
            $line->questionid = $fromform->id;
            $line->number = $i + 1;
            $line->type = $fromform->type[$i];
            $line->labelstart = $fromform->labelstart[$i];
            $line->labelmiddle = $fromform->labelmiddle[$i];
            $line->labelend = $fromform->labelend[$i];
            $line->zonestart = $fromform->zonestart[$i];
            $line->zoneend = $fromform->zoneend[$i];
            $line->id = $DB->insert_record('qtype_drawlines_lines', $line);
        }
    }

    public function save_hints($fromform, $withparts = false) {
        global $DB;
        $context = $fromform->context;

        $oldhints = $DB->get_records('question_hints',
                array('questionid' => $fromform->id), 'id ASC');

        if (!empty($fromform->hint)) {
            $numhints = max(array_keys($fromform->hint)) + 1;
        } else {
            $numhints = 0;
        }

        if ($withparts) {
            if (!empty($fromform->hintclearwrong)) {
                $numclears = max(array_keys($fromform->hintclearwrong)) + 1;
            } else {
                $numclears = 0;
            }
            if (!empty($fromform->hintshownumcorrect)) {
                $numshows = max(array_keys($fromform->hintshownumcorrect)) + 1;
            } else {
                $numshows = 0;
            }
            $numhints = max($numhints, $numclears, $numshows);
        }

        for ($i = 0; $i < $numhints; $i += 1) {
            if (html_is_blank($fromform->hint[$i]['text'])) {
                $fromform->hint[$i]['text'] = '';
            }

            if ($withparts) {
                $clearwrong = !empty($fromform->hintclearwrong[$i]);
                $shownumcorrect = !empty($fromform->hintshownumcorrect[$i]);
                $statewhichincorrect = !empty($fromform->hintoptions[$i]);
            }

            if (empty($fromform->hint[$i]['text']) && empty($clearwrong) &&
                    empty($shownumcorrect) && empty($statewhichincorrect)) {
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
                $hint->clearwrong = $clearwrong;
                $hint->shownumcorrect = $shownumcorrect;
                $hint->options = $statewhichincorrect;
            }
            $DB->update_record('question_hints', $hint);
        }

        // Delete any remaining old hints.
        $fs = get_file_storage();
        foreach ($oldhints as $oldhint) {
            $fs->delete_area_files($context->id, 'question', 'hint', $oldhint->id);
            $DB->delete_records('question_hints', array('id' => $oldhint->id));
        }
    }

    protected function make_question_instance($questiondata) {
        question_bank::load_question_definition_classes($this->name());
        return new qtype_drawlines_question();
    }

    protected function initialise_question_instance(question_definition $question, $questiondata): void {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_lines($question, $questiondata);
        //$this->initialise_combined_feedback($question, $questiondata, true);
    }

    protected function initialise_question_lines(question_definition $question, stdClass $questiondata): void {
        foreach ($questiondata->lines as $line) {
            $question->lines[$line->number -1] = $this->make_line($line);
        }
    }
    protected function initialise_combined_feedback(question_definition $question, $questiondata, $withparts = false) {
        parent::initialise_combined_feedback($question, $questiondata, $withparts);
        $question->showmisplaced = $questiondata->options->showmisplaced;
    }

    /**
     * Make a line.
     *
     * @param stdClass $linedata
     * @return data
     */
    public function make_line(stdClass $linedata): line {
        return new line($linedata->id, $linedata->questionid, $linedata->number, $linedata->type,
                $linedata->labelstart, $linedata->labelmiddle, $linedata->labelend,
                $linedata->zonestart, $linedata->zoneend);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_drawlines_options', ['questionid' => $questionid]);
        $DB->delete_records('qtype_drawlines_lines', ['questionid' => $questionid]);
        // TODO: user the answer table for storing drag items.
        //$DB->delete_records('qtype_drawlines_drags', ['questionid' => $questionid]);
        parent::delete_question($questionid, $contextid);
    }

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


            $fs = get_file_storage();

            parent::delete_files($questionid, $contextid);
            $this->delete_files_in_row_feedback($questionid, $contextid);
            $this->delete_files_in_hints($questionid, $contextid);
            $fs->delete_area_files($contextid, 'question', 'correctfeedback', $questionid);
            $fs->delete_area_files($contextid, 'question', 'partiallycorrectfeedback', $questionid);
            $fs->delete_area_files($contextid, 'question', 'incorrectfeedback', $questionid);
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $fs = get_file_storage();
        $contextid = $question->contextid;

        $output = '';
        $output .= '    <grademethod>' . $format->xml_escape($question->options->grademethod) .
                "</grademethod>\n";
        $output .= $format->write_combined_feedback($question->options,
                                                    $question->id,
                                                    $question->contextid);
        $files = $fs->get_area_files($contextid, 'qtype_drawlines', 'bgimage', $question->id);
        $output .= "    " . $this->write_files($files, 2)."\n";;

        // Export lines data.
        $indent = 4;
        $output .= "    <lines>\n";
        foreach ($question->lines as $key => $line) {
            $output .= "    <line number=\"{$line->number}\">\n";
            $output .= $format->writetext($line->type, $indent);
            $output .= $format->writetext($line->labelstart, $indent);
            $output .= $format->writetext($line->labelmiddle, $indent);
            $output .= $format->writetext($line->labelend, $indent);
            $output .= $format->writetext($line->zonestart, $indent);
            $output .= $format->writetext($line->zonestart, $indent);
            $output .= "      </line>\n";
        }
        $output .= "    </lines>\n";

        return $output;
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'drawlines') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'drawlines';

        $question->showmisplaced = array_key_exists('showmisplaced',
                                                    $format->getpath($data, ['#'], []));

        $filexml = $format->getpath($data, ['#', 'file'], []);
        $question->bgimage = $format->import_files_as_draft($filexml);
        $drags = $data['#']['drag'];
        $question->drags = [];

        foreach ($drags as $dragxml) {
            $dragno = $format->getpath($dragxml, ['#', 'no', 0, '#'], 0);
            $dragindex = $dragno - 1;
            $question->drags[$dragindex] = [];
            $question->drags[$dragindex]['label'] =
                        $format->getpath($dragxml, array('#', 'text', 0, '#'), '', true);
            if (array_key_exists('infinite', $dragxml['#'])) {
                $question->drags[$dragindex]['noofdrags'] = 0; // Means infinite in the form.
            } else {
                // Defaults to 1 if 'noofdrags' not set.
                $question->drags[$dragindex]['noofdrags'] = $format->getpath($dragxml, array('#', 'noofdrags', 0, '#'), 1);
            }
        }

        $format->import_combined_feedback($question, $data, true);
        $format->import_hints($question, $data, true, true,
                $format->get_format($question->questiontextformat));

        return $question;
    }

    public function get_random_guess_score($questiondata) {
        return null;
    }
}
