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

namespace drawlines;

use question_attempt_step;
use question_classified_response;
use question_state;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/drawlines/tests/helper.php');
require_once($CFG->dirroot . '/question/type/drawlines/question.php');


/**
 * Unit tests for DrawLines question definition class.
 *
 * @package   qtype_drawlines
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_test extends \basic_testcase {

    public function test_get_expected_data() {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $expected = [];
        foreach ($question->lines as $key => $line) {
            $expected['zonestart_' . $line->number] = PARAM_NOTAGS;
            $expected['zoneend_' . $line->number] = PARAM_NOTAGS;
        }
        $this->assertEquals($expected, $question->get_expected_data());
    }

    public function test_get_correct_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = [];
        foreach ($question->lines as $key => $line) {
            $correctresponse['zonestart_'  . $line->number] = $line->zonestart;
            $correctresponse['zoneend_' . $line->number] = $line->zoneend;
        }
        $this->assertEquals($correctresponse, $question->get_correct_response());
    }

    public function test_is_complete_response() {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = $question->get_correct_response();
        $this->assertTrue($question->is_complete_response($correctresponse));
        $this->assertFalse($question->is_complete_response([]));
        $this->assertTrue($question->is_complete_response(['zonestart_1' => '10,10', 'zoneend_1' => '300,10', 'zonestart_2' => '10,100', 'zoneend_2' => '300,100']));
        $this->assertTrue($question->is_complete_response(['zonestart_1' => '10,10', 'zoneend_1' => '200,10', 'zonestart_2' => '10,100', 'zoneend_2' => '300,100']));
        $this->assertFalse($question->is_complete_response(['zonestart_1' => '10,10', 'zoneend_1' => '300,10']));
        $this->assertFalse($question->is_complete_response(['zonestart_2' => '10,100', 'zoneend_2' => '300,100']));
    }

    public function test_is_same_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $response = $question->get_correct_response();
        $expected = [
                'zonestart_1' => '10,10;12', 'zoneend_1' => '300,10;12',
                'zonestart_2' => '10,100;12', 'zoneend_2' => '300,100;12'
        ];
        $this->assertEquals($expected, $response);

        $this->assertTrue($question->is_same_response(
                ['zonestart_1' => '100,100', 'zoneend_1' => '100,200', 'zonestart_2' => '200,100', 'zoneend_2' => '200,200'],
                ['zonestart_1' => '100,100', 'zoneend_1' => '100,200', 'zonestart_2' => '200,100', 'zoneend_2' => '200,200']));

        $this->assertFalse($question->is_same_response(
                ['zonestart_1' => '100,100', 'zoneend_1' => '100,200', 'zonestart_2' => '200,100', 'zoneend_2' => '200,200'],
                ['zonestart_1' => '10,100', 'zoneend_1' => '100,300', 'zonestart_2' => '200,100', 'zoneend_2' => '200,200']));
     }

    public function test_get_question_summary(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $summary = $question->get_question_summary();
        $this->assertNotEmpty($summary);

        $expected = 'Draw 2 lines on the map. A line segennt from A (line starting point) to B (line Ending point), ' .
                'and another one from C to D. A is ..., B is ..., C is ... and D is ...';
        $this->assertEquals($expected, $summary);
    }

    public function test_summarise_response() {
    }

    public function test_get_random_guess_score() {
        $dl = \test_question_maker::make_question('drawlines');
        $this->assertEquals(null, $dl->get_random_guess_score());
    }

    public function test_get_num_parts_right() {
        $dl = \test_question_maker::make_question('drawlines');
        $dl->start_attempt(new question_attempt_step(), 1);

    }
}
