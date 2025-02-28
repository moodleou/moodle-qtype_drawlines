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

namespace qtype_drawlines;

use question_possible_response;
use stdClass;
use question_bank;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/drawlines/tests/helper.php');


/**
 * Unit tests for the draw lines question definition class.
 *
 * @package    qtype_drawlines
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_drawlines
 */
final class questiontype_test extends \advanced_testcase {
    /** @var qtype_drawlines instance of the question type class to test. */
    protected $qtype;

    protected function setUp(): void {
        parent::setUp();
        $this->qtype = question_bank::get_qtype('drawlines');
    }

    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }

    /**
     * Check the name of this question type.
     *
     * @covers \question_type::name
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'drawlines');
    }

    /**
     * Check that this question type can analise responses.
     *
     * @covers \question_type::can_analyse_responses
     */
    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Make line object and check if it was constructed correctly.
     *
     * @covers \qtype_drawlines::make_line
     */
    public function test_make_line(): void {
        $this->resetAfterTest();

        $line = new stdClass();
        $line->id = 11;
        $line->questionid = 20;
        $line->number = 1;
        $line->type = line::TYPE_LINE_SEGMENT;
        $line->labelstart = 's-label';
        $line->labelmiddle = 'm-lable';
        $line->labelend = 'e-label';
        $line->zonestart = '10,10;12';
        $line->zoneend = '200,10;12';

        $expected = (array)$line;
        $actual = (array)$this->qtype->make_line($line);

        foreach ($actual as $attr => $value) {
            $this->assertEquals($expected[$attr], $value);
        }
    }

    /**
     * Get the question options from the form and check the entries in the database table.
     *
     * @covers \qtype_drawlines::get_question_options
     */
    public function test_get_question_options(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category([]);
        $q = $questiongenerator->create_question('drawlines', 'mkmap_twolines', ['category' => $cat->id]);
        $questiondata = question_bank::load_question_data($q->id);

        // Question data contains options and lines and return true.
        $trueorfalse = $this->qtype->get_question_options($questiondata);
        $this->assertTrue($trueorfalse);
        $options = $DB->get_record('qtype_drawlines_options', ['questionid' => $questiondata->id]);
        $this->assertEquals('partial', $options->grademethod);
        $this->assertEquals('Well done!', $options->correctfeedback);
        $this->assertEquals(FORMAT_HTML, $options->correctfeedbackformat);
        $this->assertEquals('Parts, but only parts, of your response are correct.', $options->partiallycorrectfeedback);
        $this->assertEquals(FORMAT_HTML, $options->partiallycorrectfeedbackformat);
        $this->assertEquals('That is not right at all.', $options->incorrectfeedback);
        $this->assertEquals(FORMAT_HTML, $options->incorrectfeedbackformat);
        $this->assertEquals(1, $options->shownumcorrect);
        $this->assertEquals(0, $options->showmisplaced);

        $this->assertEquals($options->id, $questiondata->options->id);
        $this->assertEquals($options->questionid, $questiondata->options->questionid);
        $this->assertEquals($options->grademethod, $questiondata->options->grademethod);
        $this->assertEquals($options->correctfeedback, $questiondata->options->correctfeedback);
        $this->assertEquals($options->correctfeedbackformat, $questiondata->options->correctfeedbackformat);
        $this->assertEquals($options->partiallycorrectfeedback, $questiondata->options->partiallycorrectfeedback);
        $this->assertEquals($options->partiallycorrectfeedbackformat, $questiondata->options->partiallycorrectfeedbackformat);
        $this->assertEquals($options->incorrectfeedback, $questiondata->options->incorrectfeedback);
        $this->assertEquals($options->incorrectfeedbackformat, $questiondata->options->incorrectfeedbackformat);
        $this->assertEquals($options->shownumcorrect, $questiondata->options->shownumcorrect);
        $this->assertEquals($options->showmisplaced, $questiondata->options->showmisplaced);

        $lines = $DB->get_records('qtype_drawlines_lines', ['questionid' => $questiondata->id]);
        foreach ($questiondata->lines as $key => $line) {
            $this->assertEquals($line->id, $lines[$key]->id);
            $this->assertEquals($line->questionid, $lines[$key]->questionid);
            $this->assertEquals($line->number, $lines[$key]->number);
            $this->assertEquals($line->type, $lines[$key]->type);
            $this->assertEquals($line->labelstart, $lines[$key]->labelstart);
            $this->assertEquals($line->labelmiddle, $lines[$key]->labelmiddle);
            $this->assertEquals($line->labelend, $lines[$key]->labelend);
            $this->assertEquals($line->zonestart, $lines[$key]->zonestart);
            $this->assertEquals($line->zoneend, $lines[$key]->zoneend);
        }
    }

    /**
     * Save the line objects from the form and check the entries in the database table.
     *
     * @covers \qtype_drawlines::save_lines
     */
    public function test_save_lines(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category([]);
        $q = $questiongenerator->create_question('drawlines', 'mkmap_twolines', ['category' => $cat->id]);
        $questiondata = question_bank::load_question_data($q->id);

        // Save the lines for this question.
        $formdata = \test_question_maker::get_question_form_data('drawlines', 'mkmap_twolines');
        $this->qtype->save_lines($formdata);
        $lines = $DB->get_records('qtype_drawlines_lines', ['questionid' => $questiondata->id]);
        foreach ($lines as $id => $line) {
            $this->assertEquals($line->id, $questiondata->lines[$id]->id);
            $this->assertEquals($line->questionid, $questiondata->lines[$id]->questionid);
            $this->assertEquals($line->number, $questiondata->lines[$id]->number);
            $this->assertEquals($line->type, $questiondata->lines[$id]->type);
            $this->assertEquals($line->labelstart, $questiondata->lines[$id]->labelstart);
            $this->assertEquals($line->labelmiddle, $questiondata->lines[$id]->labelmiddle);
            $this->assertEquals($line->labelend, $questiondata->lines[$id]->labelend);
            $this->assertEquals($line->zonestart, $questiondata->lines[$id]->zonestart);
            $this->assertEquals($line->zoneend, $questiondata->lines[$id]->zoneend);
        }
    }

    /**
     * Test get_possible_responses for 'partial' grading method.
     *
     * @covers \qtype_drawlines::get_possible_responses
     */
    public function test_get_possible_responses_partial(): void {
        $this->resetAfterTest(false);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category([]);
        $createdquestion = $generator->create_question('drawlines', 'mkmap_twolines',
            ['category' => $category->id, 'name' => 'Test question', 'grademethod' => 'partial']);
        $q = question_bank::load_question_data($createdquestion->id);
        $this->assertEquals([
            'Line 1 (10,10) (10,200)' => [
                1 => new question_possible_response(get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1),
            ],
            'Line 1 (10,10)' => [
                2 => new question_possible_response(get_string('valid_startcoordinates', 'qtype_drawlines'), 0.5),
            ],
            'Line 1 (10,200)' => [
                3 => new question_possible_response(get_string('valid_endcoordinates', 'qtype_drawlines'), 0.5),
            ],
            'Line 1' => [
                4 => new question_possible_response('Incorrect response', 0),
                null => question_possible_response::no_response(),
            ],
            'Line 2 (300,10) (300,200)' => [
                1 => new question_possible_response(get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1),
            ],
            'Line 2 (300,10)' => [
                2 => new question_possible_response(get_string('valid_startcoordinates', 'qtype_drawlines'), 0.5),
            ],
            'Line 2 (300,200)' => [
                3 => new question_possible_response(get_string('valid_endcoordinates', 'qtype_drawlines'), 0.5),
            ],
             'Line 2' => [
                 4 => new question_possible_response('Incorrect response', 0),
                null => question_possible_response::no_response(),
             ],
        ], $this->qtype->get_possible_responses($q));
    }

    /**
     * Test get_possible_responses for 'All-or-nothing' grading method.
     *
     * @covers \qtype_drawlines::get_possible_responses
     */
    public function test_get_possible_responses_allornone(): void {
        $this->resetAfterTest(false);
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category([]);
        $createdquestion = $generator->create_question('drawlines', 'mkmap_twolines',
            ['category' => $category->id, 'name' => 'Test question', 'grademethod' => 'allornone']);
        $q = question_bank::load_question_data($createdquestion->id);
        $this->assertEquals([
            'Line 1 (10,10) (10,200)' => [
                1 => new question_possible_response(get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1),
            ],
            'Line 1 (10,10)' => [
                2 => new question_possible_response(get_string('valid_startcoordinates', 'qtype_drawlines'), 0),
            ],
            'Line 1 (10,200)' => [
                3 => new question_possible_response(get_string('valid_endcoordinates', 'qtype_drawlines'), 0),
            ],
            'Line 1' => [
                4 => new question_possible_response('Incorrect response', 0),
                null => question_possible_response::no_response(),
            ],
            'Line 2 (300,10) (300,200)' => [
                1 => new question_possible_response(get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1),
            ],
            'Line 2 (300,10)' => [
                2 => new question_possible_response(get_string('valid_startcoordinates', 'qtype_drawlines'), 0),
            ],
            'Line 2 (300,200)' => [
                3 => new question_possible_response(get_string('valid_endcoordinates', 'qtype_drawlines'), 0),
            ],
            'Line 2' => [
                4 => new question_possible_response('Incorrect response', 0),
                null => question_possible_response::no_response(),
            ],
        ], $this->qtype->get_possible_responses($q));
    }

    public function test_get_random_guess_score(): void {
        $this->resetAfterTest();
        $qdata = \test_question_maker::get_question_data('drawlines', 'mkmap_twolines');
        $this->assertEquals(0, $this->qtype->get_random_guess_score($qdata));
    }
}
