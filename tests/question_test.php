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
 * @covers \qtype_drawlines_question
 */
final class question_test extends \basic_testcase {

    public function test_get_expected_data(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $expected = [
                'c0' => PARAM_RAW,
                'c1' => PARAM_RAW,
        ];
        $this->assertEquals($expected, $question->get_expected_data());
    }

    public function test_get_correct_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = [
                'c0' => '10,10 300,10',
                'c1' => '10,200 300,200',
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
                        'c0' => '10,10 200,10',
                        'c1' => '10,100 200,100',
                ]
        ));
        $this->assertFalse($question->is_complete_response(['c0' => '10,10 300,10']));
        $this->assertFalse($question->is_complete_response(['c1' => '10,100 300,100']));
        $this->assertTrue($question->is_complete_response(['c0' => '10,10 300,10', 'c1' => '10,100 300,100']));
    }

    public function test_is_gradable_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);
        $correctresponse = $question->get_correct_response();
        $this->assertTrue($question->is_gradable_response($correctresponse));
        $this->assertFalse($question->is_gradable_response([]));
        $this->assertTrue($question->is_gradable_response(['c0' => '10,10 300,10', 'c1' => '10,100 200,100']));
        if ($question->grademethod === 'partial') {
            $this->assertTrue($question->is_gradable_response(['c0' => '10,10 300,10']));
            $this->assertTrue($question->is_gradable_response(['c1' => '10,100 300,100']));
        }
        $question->grademethod = 'allnone';
        if ($question->grademethod === 'allnone') {
            $this->assertTrue($question->is_gradable_response(['c0' => '10,10 300,10']));
            $this->assertTrue($question->is_gradable_response(['c1' => '10,100 300,100']));
        }
    }

    public function test_is_same_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $response = $question->get_correct_response();
        $expected = ['c0' => '10,10 300,10', 'c1' => '10,200 300,200'];
        $this->assertEquals($expected, $response);

        $this->assertTrue($question->is_same_response(
                ['c0' => '100,100 100,200', 'c1' => '200,100 200,200'],
                ['c0' => '100,100 100,200', 'c1' => '200,100 200,200']
        ));

        $this->assertFalse($question->is_same_response(
                ['c0' => '100,100 100,200', 'c1' => '200,100 200,200'],
                ['c0' => '10,100 100,200', 'c1' => '200,100 200,200']
        ));
    }

    public function test_get_question_summary(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $summary = $question->get_question_summary();
        $this->assertNotEmpty($summary);

        $expected = 'Draw 2 lines on the map. A line segment from A (line starting point) to B (line Ending point), ' .
                'and another one from C to D. A is ..., B is ..., C is ... and D is ...';
        $this->assertEquals($expected, $summary);
    }

    public function test_summarise_response(): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        // Correct responses with full mark for both Lines (mark = 1).
        $correctresponse = $question->get_correct_response();
        $expected = 'Line 1: 10,10 300,10, Line 2: 10,200 300,200';
        $actual = $question->summarise_response($correctresponse);
        $this->assertEquals($expected, $actual);

        // Partially correct responses with full marks for Line 1 and half of mark for Line 2 (mark = 0.75).
        $expected = 'Line 1: 10,10 300,10, Line 2: 10,200 300,123';
        $actual = $question->summarise_response(['c0' => '10,10 300,10', 'c1' => '10,200 300,123']);
        $this->assertEquals($expected, $actual);

        // Partially correct responses with full marks for Line 1 and no mark for Line 2 (mark = 0.5).
        $expected = 'Line 1: 10,10 300,10, Line 2: 10,123 300,123';
        $actual = $question->summarise_response(['c0' => '10,10 300,10', 'c1' => '10,123 300,123']);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_num_parts_right_grade_partial(): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        $correctresponse = $question->get_correct_response();
        [$numpartright, $total] = $question->get_num_parts_right_grade_partial($correctresponse);
        $this->assertEquals(4, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,123', 'c1' => '10,123 300,123'];
        [$numpartright, $total] = $question->get_num_parts_right_grade_partial($response);
        $this->assertEquals(1, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];
        [$numpartright, $total] = $question->get_num_parts_right_grade_partial($response);
        $this->assertEquals(2, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];
        [$numpartright, $total] = $question->get_num_parts_right_grade_partial($response);
        $this->assertEquals(3, $numpartright);
        $this->assertEquals(4, $total);
    }

    public function test_get_num_parts_right_grade_allornone(): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        $correctresponse = $question->get_correct_response();
        [$numright, $total] = $question->get_num_parts_right_grade_allornone($correctresponse);
        $this->assertEquals(2, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,123', 'c1' => '10,123 300,123'];
        [$numright, $total] = $question->get_num_parts_right_grade_allornone($response);
        $this->assertEquals(0, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];
        [$numright, $total] = $question->get_num_parts_right_grade_allornone($response);
        $this->assertEquals(1, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];
        [$numright, $total] = $question->get_num_parts_right_grade_allornone($response);
        $this->assertEquals(1, $numright);
        $this->assertEquals(2, $total);
    }

    public function test_retrieve_numright_numtotal(): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        // The grade method is set to 'Give partial credit' by default.
        $correctresponse = $question->get_correct_response();
        [$numpartright, $total] = $question->retrieve_numright_numtotal($correctresponse);
        $this->assertEquals(4, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,123', 'c1' => '10,123 300,123'];
        [$numpartright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(1, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];
        [$numpartright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(2, $numpartright);
        $this->assertEquals(4, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];
        [$numpartright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(3, $numpartright);
        $this->assertEquals(4, $total);

        // Set the  grade method to 'All-or-nothing'.
        $question->grademethod = 'allnone';
        $correctresponse = $question->get_correct_response();
        [$numright, $total] = $question->retrieve_numright_numtotal($correctresponse);
        $this->assertEquals(2, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,123', 'c1' => '10,123 300,123'];
        [$numright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(0, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];
        [$numright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(1, $numright);
        $this->assertEquals(2, $total);

        $response = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];
        [$numright, $total] = $question->retrieve_numright_numtotal($response);
        $this->assertEquals(1, $numright);
        $this->assertEquals(2, $total);
    }

    public function test_grade_response(): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        $correctresponse = $question->get_correct_response();
        $this->assertEquals([1, question_state::$gradedright], $question->grade_response($correctresponse));

        $partiallycorrectresponse = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];
        $this->assertEquals([0.75, question_state::$gradedpartial], $question->grade_response($partiallycorrectresponse));

        $partiallycorrectresponse = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];
        $this->assertEquals([0.5, question_state::$gradedpartial], $question->grade_response($partiallycorrectresponse));

        $wrongresponse = ['c0' => '123,10 123,10', 'c1' => '10,123 300,123'];
        $this->assertEquals([0, question_state::$gradedwrong], $question->grade_response($wrongresponse));
    }

    public function test_compute_final_grade(): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        // Single try.
        $totaltries = 1;
        $responses[1] = ['c0' => '10,123 300,123', 'c1' => '10,123 300,123']; // Both lines are incorrect.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEquals(0, $fraction, 'Incorrect responses should return fraction of 0');

        $responses[1] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123'];// Line 1 is correct and line 2 is incorrect.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEquals(0.5, $fraction,
                'Partially correct responses(line 1 is correct and line 2 is incorrect) should return fraction of 0.5');

        $responses[1] = ['c0' => '10,10 300,10', 'c1' => '10,200 300,123'];// Line 1 is correct and line 2 is partially correct.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEquals($fraction, 0.75,
                'Partially correct responses(line 1 is correct and line 2 is half-correct) should return fraction of 0.75');

        $responses[1] = $question->get_correct_response(); // Both lines are correct.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEquals($fraction, 1, 'All correct responses should return fraction of 1');

        // Multiple tries with penalties, totaltries set to 3.
        $totaltries = 3;

        // First attempt wrong, second attempt partially correct, third attemmpt correct.
        $responses[1] = ['c0' => '10,123 300,123', 'c1' => '10,123 300,123']; // Both lines are incorrect.
        $responses[2] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123']; // Line 1 is correct and line 2 is incorrect.
        $responses[3] = $question->get_correct_response(); // 4 correct 0 incorrect.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEqualsWithDelta( 0.4444445, $fraction, 0.0000001);

        // First attempt partially correct, second attempt partially correct, third attemmpt correct.
        $responses[1] = ['c0' => '10,10 300,123', 'c1' => '10,123 300,123']; // Line 1 is partially correct and line 2 is incorrect.
        $responses[2] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,200']; // Line 1 is correct and line 2 is partially correct.
        $responses[3] = $question->get_correct_response(); // Both lines are correct.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEqualsWithDelta(0.5555556, $fraction, 0.0000001);

        // First attempt wrong, second attempt correct.
        $responses[1] = ['c0' => '10,123 300,123', 'c1' => '10,123 300,123']; // Both lines are incorrect.
        $responses[2] = $question->get_correct_response(); // 4 correct 0 incorrect.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEqualsWithDelta( 0.6666667, $fraction, 0.0000001);

        // First attempt partially correct, second attempt correct.
        $responses[1] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,200']; // Line 1 is correct and line 2 is correct.
        $responses[2] = $question->get_correct_response(); // Both lines are correct.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEqualsWithDelta(0.8888889, $fraction, 0.0000001);

        // First attempt correct.
        $responses[1] = $question->get_correct_response(); // Both lines are correct (4 correct coordinates).
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEquals(1, $fraction, 'On first attempt, correct responses should return fraction of 1');

        // First attempt wrong, second attempt partially correct, third attemmpt correct.
        $responses[1] = ['c0' => '10,123 10,123', 'c1' => '173,200 173,200']; // Both lines are incorrect.
        $responses[2] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,123']; // Line 1 is correct and line 2 is incorrect.
        $responses[3] = ['c0' => '10,10 300,10', 'c1' => '10,123 300,200']; // Line 1 is correct and line 2 is partially correct.
        $fraction = $question->compute_final_grade($responses, $totaltries);
        $this->assertEqualsWithDelta( 0.0833334, $fraction, 0.0000001);
    }
}
