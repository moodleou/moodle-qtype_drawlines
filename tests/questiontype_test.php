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

use stdClass;
use question_bank;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/drawlines/tests/helper.php');


/**
 * Unit tests for the drawlines question definition class.
 *
 * @package    qtype_drawlines
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questiontype_test extends \advanced_testcase {
    /** @var qtype_drawlines instance of the question type class to test. */
    protected $qtype;

    protected function setUp(): void {
        $this->qtype = question_bank::get_qtype('drawlines');;
    }

    protected function tearDown(): void {
        $this->qtype = null;
    }

    public function test_name() {
        $this->assertEquals($this->qtype->name(), 'drawlines');
    }

    public function test_can_analyse_responses() {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    public function test_make_line() {
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
     * Test to make sure that loading of question options works, including in an error case.
     */
    public function test_get_question_options() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->setAdminUser();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category([]);
        $q = $questiongenerator->create_question('drawlines', 'mkmap_twolines', ['category' => $cat->id]);
        $questiondata = question_bank::load_question_data($q->id);
        $formdata = \test_question_maker::get_question_form_data('drawlines', 'mkmap_twolines');

        $question = $this->qtype->get_question_options($questiondata);
        $options = $question->options;
        $this->assertEquals($formdata->grademethod, $options->grademethod);
        $this->assertEquals($formdata->correctfeedback['text'], $options->correctfeedback);
        $this->assertEquals($formdata->correctfeedback['format'], $options->correctfeedbackformat);
        $this->assertEquals($formdata->partiallycorrectfeedback['text'], $options->partiallycorrectfeedback);
        $this->assertEquals($formdata->partiallycorrectfeedback['format'], $options->partiallycorrectfeedbackformat);
        $this->assertEquals($formdata->incorrectfeedback['text'], $options->incorrectfeedback);
        $this->assertEquals($formdata->incorrectfeedback['format'], $options->incorrectfeedbackformat);
        $this->assertEquals($formdata->shownumcorrect, $options->shownumcorrect);

        foreach ($question->lines as $line) {
            $this->assertEquals($line->type, $formdata->type[$line->number -1]);
            $this->assertEquals($line->labelstart, $formdata->labelstart[$line->number -1]);
            $this->assertEquals($line->labelmiddle, $formdata->labelmiddle[$line->number -1]);
            $this->assertEquals($line->labelend, $formdata->labelend[$line->number -1]);
            $this->assertEquals($line->zonestart, $formdata->zonestart[$line->number -1]);
            $this->assertEquals($line->zoneend, $formdata->zoneend[$line->number -1]);
        };
    }

    public function test_save_lines() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
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
            $this->assertEquals($line->type, $questiondata->lines[$id]->type);
            $this->assertEquals($line->number, $questiondata->lines[$id]->number);
            $this->assertEquals($line->labelstart, $questiondata->lines[$id]->labelstart);
            $this->assertEquals($line->labelmiddle, $questiondata->lines[$id]->labelmiddle);
            $this->assertEquals($line->labelend, $questiondata->lines[$id]->labelend);
            $this->assertEquals($line->zonestart, $questiondata->lines[$id]->zonestart);
            $this->assertEquals($line->zoneend, $questiondata->lines[$id]->zoneend);
        }
    }
}
