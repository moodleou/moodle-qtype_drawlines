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
use qtype_drawlines\line;


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

        $expected = [
                'c1' => PARAM_RAW, 'c2' => PARAM_RAW,
                'c3' => PARAM_RAW, 'c4' => PARAM_RAW
        ];
        $this->assertEquals($expected, $question->get_expected_data());
    }

    public function test_get_correct_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse =
                [
                        'c1' => '10,10', 'c2' => '300,10',
                        'c3' => '300,10', 'c4' => '300,100',
                ];
        $this->assertEquals($correctresponse, $question->get_correct_response());
    }

    public function test_is_complete_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = $question->get_correct_response();
        $this->assertTrue($question->is_complete_response($correctresponse));
        $this->assertFalse($question->is_complete_response([]));
        $this->assertTrue($question->is_complete_response(
                [
                        'c1' => '10,10', 'c2' => '200,10',
                        'c3' => '10,100', 'c4' => '200,100'
                ]
        ));
        $this->assertFalse($question->is_complete_response(['c1' => '10,10', 'c2' => '300,10']));
        $this->assertFalse($question->is_complete_response(['c3' => '10,100', 'c4' => '300,100']));
    }

    public function test_is_gradable_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = $question->get_correct_response();
        $this->assertTrue($question->is_gradable_response($correctresponse));
        $this->assertFalse($question->is_gradable_response([]));
        $this->assertTrue($question->is_gradable_response(
                [
                        'c1' => '10,10', 'c2' => '300,10',
                        'c3' => '10,100', 'c4' => '200,100'
                ]
        ));
        $this->assertFalse($question->is_gradable_response(['c1' => '10,10', 'c2' => '300,10']));
        $this->assertFalse($question->is_gradable_response(['c3' => '10,100', 'c4' => '300,100']));
    }

    public function test_is_same_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $response = $question->get_correct_response();
        $expected = ['c1' => '10,10', 'c2' => '300,10', 'c3' => '300,10', 'c4' => '300,100'];
        $this->assertEquals($expected, $response);

        $this->assertTrue($question->is_same_response(
                ['c1' => '100,100', 'c2' => '100,200', 'c3' => '200,100', 'c4' => '200,200'],
                ['c1' => '100,100', 'c2' => '100,200', 'c3' => '200,100', 'c4' => '200,200']
        ));

        $this->assertFalse($question->is_same_response(
                ['c1' => '100,100', 'c2' => '100,200', 'c3' => '200,100', 'c4' => '200,200'],
                ['c1' => '10,100', 'c2' => '100,200', 'c3' => '200,100', 'c4' => '200,200']
        ));
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
        $question = \test_question_maker::make_question('drawlines');
        $this->assertEquals(null, $question->get_random_guess_score());
    }

    public function test_get_num_parts_right() {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        $response = $question->get_correct_response();
        $this->assertEquals(4, $question->get_num_parts_right($response));

        $response = ['c1' => '10,10', 'c2' => '200,10', 'c3' => '10,100', 'c4' => '300,100'];
        $this->assertEquals(2, $question->get_num_parts_right($response));
    }
}
