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
 * Test helper for the draw lines  question type.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @author    The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines_test_helper extends question_test_helper {

    #[\Override]
    public function get_test_questions(): array {
        return ['mkmap_twolines'];
    }

    /**
     * Get the question data, as it would be loaded by get_question_options.
     *
     * @return stdClass
     */
    public function get_drawlines_question_data_mkmap_twolines(): stdClass {
        global $CFG, $USER;

        $qdata = new stdClass();
        question_bank::load_question_definition_classes('drawlines');
        $qdata = new qtype_drawlines_question();
        $bgdraftitemid = 0;
        file_prepare_draft_area($bgdraftitemid, null, null, null, null);
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $filerecord->contextid = context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $bgdraftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = 'mkmap.png';
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot .
                '/question/type/drawlines/tests/fixtures/mkmap.png');

        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->qtype = 'drawlines';
        $qdata->name = 'drawlines_mkmap01';
        $qdata->questiontext = 'Draw 2 lines on the map. A line segment from A (line starting point) to B (line Ending point),' .
                    ' and another one from C to D. A is ..., B is ..., C is ... and D is ...';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'We draw lines from a starting to an end point.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $qdata->versionid = 0;
        $qdata->version = 1;
        $qdata->questionbankentryid = 0;
        $qdata->options = new stdClass();
        $qdata->options->grademethod = 'partial';
        $qdata->options->correctfeedback = test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->partiallycorrectfeedback = test_question_maker::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK;
        $qdata->options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->incorrectfeedback = test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = 1;
        $qdata->options->showmisplaced = 0;

        $qdata->lines = [
                1 => (object)[
                        'id' => 11,
                        'number' => 1,
                        'type' => line::TYPE_LINE_SEGMENT,
                        'labelstart' => 'Start 1',
                        'labelmiddle' => 'Mid 1',
                        'labelend' => '',
                        'zonestart' => '100,100;10',
                        'zoneend' => '322,213;10',
                ],
                2 => (object)[
                        'number' => 2,
                        'type' => line::TYPE_LINE_SEGMENT,
                        'labelstart' => 'Start 2',
                        'labelmiddle' => '',
                        'labelend' => '',
                        'zonestart' => '100,100;10',
                        'zoneend' => '322,213;10',
                ],
        ];
        $qdata->hints = [
                1 => (object) [
                        'hint' => 'Hint 1.',
                        'hintformat' => FORMAT_HTML,
                        'shownumcorrect' => 1,
                        'showmisplaced' => 0,
                        'options' => 0,
                ],
                2 => (object) [
                        'hint' => 'Hint 2.',
                        'hintformat' => FORMAT_HTML,
                        'shownumcorrect' => 1,
                        'showmisplaced' => 1,
                        'options' => 1,
                ],
        ];
        return $qdata;
    }


    /**
     * Return the form data for a DrawLines with 2 lines.
     *
     * @return stdClass data to create a drawliness question.
     */
    public function get_drawlines_question_form_data_mkmap_twolines(): stdClass {
        global $CFG, $USER;
        $fromform = new stdClass();
        $fromform->context = \context_user::instance($USER->id);

        $bgdraftitemid = 0;
        file_prepare_draft_area($bgdraftitemid, null, null, null, null);
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $filerecord->contextid = context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $bgdraftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = 'mkmap.png';
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot .
                '/question/type/drawlines/tests/fixtures/mkmap.png');

        $fromform->id = 123;
        $fromform->name = 'MK landmarks';
        $fromform->questiontext = [
            'text' => 'Draw 2 lines on the map. A line segment from A (line starting point) to B (line Ending point),' .
                    ' and another one from C to D. A is ..., B is ..., C is ... and D is ...',
            'format' => FORMAT_HTML,
        ];
        $fromform->defaultmark = 1;
        $fromform->grademethod = get_config('qtype_drawlines', 'grademethod');
        $fromform->generalfeedback = [
            'text' => 'We draw lines from a starting to an end point.',
            'format' => FORMAT_HTML,
        ];
        $fromform->bgimage = $bgdraftitemid;
        $fromform->grademethod = 'partial';

        // We create 2 lines in this question.
        $fromform->numberoflines = 2;
        $fromform->type = ['0' => line::TYPE_LINE_SEGMENT, '1' => line::TYPE_LINE_SEGMENT];
        $fromform->labelstart = ['0' => 'Start 1', '1' => 'Start 2'];
        $fromform->labelmiddle = ['0' => 'Mid 1', '1' => 'Mid 2'];
        $fromform->labelend = ['0' => '', '1' => 'End 2'];
        $fromform->zonestart = ['0' => '10,10;12', '1' => '10,100;12'];
        $fromform->zoneend = ['0' => '300,10;12', '1' => '300,100;12'];

        test_question_maker::set_standard_combined_feedback_form_data($fromform);

        $fromform->penalty = '0.3333333';
        $fromform->hint = [
            [
                'text' => 'You are trying to draw 2 lines by placing the start and end markers for each line on the map.',
                'format' => FORMAT_HTML,
            ],
            [
                'text' => 'You have to find the positins for start and end of each line as described in the question text.',
                'format' => FORMAT_HTML,
            ],
        ];
        $fromform->hintshownumcorrect = [1, 1];
        $fromform->hintclearwrong = [0, 1];
        $fromform->hintoptions = [0, 1];

        $fromform->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

        return $fromform;
    }

    /**
     * Returns a qtype_drawlines question.
     *
     * @return qtype_drawlines_question
     */
    public function make_drawlines_question_mkmap_twolines(): qtype_drawlines_question {
        global $CFG, $USER;

        question_bank::load_question_definition_classes('drawlines');
        $question = new qtype_drawlines_question();
        $bgdraftitemid = 0;
        file_prepare_draft_area($bgdraftitemid, null, null, null, null);
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $filerecord->contextid = context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $bgdraftitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = 'mkmap.png';
        $fs->create_file_from_pathname($filerecord, $CFG->dirroot .
                '/question/type/drawlines/tests/fixtures/mkmap.png');
        $question->id = 1234;
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        $question->qtype = 'qtype_drawlines_question';
        $question->name = 'drawlines_mkmap_twolines';
        $question->questiontext = 'Draw 2 lines on the map. A line segennt from A (line starting point) to B (line Ending point),' .
                ' and another one from C to D. A is ..., B is ..., C is ... and D is ...';
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = 'We draw lines from a starting to an end point.';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = 1;
        $question->length = 1;
        $question->penalty = 0.3333333;
        $question->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $question->versionid = 0;
        $question->version = 1;
        $question->questionbankentryid = 0;
        $question->grademethod = 'partial';
        $question->correctfeedback = test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $question->correctfeedbackformat = FORMAT_HTML;
        $question->partiallycorrectfeedback = test_question_maker::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK;
        $question->partiallycorrectfeedbackformat = FORMAT_HTML;
        $question->incorrectfeedback = test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $question->incorrectfeedbackformat = FORMAT_HTML;
        $question->shownumcorrect = 1;
        $question->showmisplaced = 0;

        $question->lines = [
                0 => new line(
                        11, $question->id, 1, line::TYPE_LINE_SEGMENT,
                        'Start 1', 'Mid 1', '', '10,10;12', '300,10;12'),
                1 => new line(
                        11, $question->id, 1, line::TYPE_LINE_SEGMENT,
                        'Start 2', '', '', '10,100;12', '300,100;12'),
        ];
        $question->hints = [
                1 => new question_hint_with_parts(1, 'Hint 1.', FORMAT_HTML, 1, 0),
                2 => new question_hint_with_parts(2, 'Hint 2.', FORMAT_HTML, 1, 1),
        ];

        return  $question;
    }

}
