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
 * Unit tests for draw lines question definition class.
 *
 * @package   qtype_drawlines
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qtype_drawlines_question
 */
final class question_test extends \basic_testcase {

    /** @var string Line1 right start. */
    const L1_RIGHT_START = '10,10';

    /** @var string Line1 wrong start. */
    const L1_WRONG_START = '10,123';

    /** @var string Line1 right end. */
    const L1_RIGHT_END = '300,10';

    /** @var string Line1 wrong end. */
    const L1_WRONG_END = '300,123';

    /** @var string Line1 right start. */
    const L2_RIGHT_START = '10,200';

    /** @var string Line2 wrong start. */
    const L2_WRONG_START = '10,123';

    /** @var string Line2 right end. */
    const L2_RIGHT_END = '300,200';

    /** @var string Line2 wrong end. */
    const L2_WRONG_END = '300,123';

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

    /**
     * Data provider for methods taking question response {@see summarise_response}.
     *
     * @return array[]
     */
    public static function summarise_response_provider(): array {
        $l1rightstart = self::L1_RIGHT_START;
        $l1wrongstart = self::L1_WRONG_START;
        $l1rightend = self::L1_RIGHT_END;
        $l1wrongend = self::L1_WRONG_END;

        $l2rightstart = self::L2_RIGHT_START;
        $l2wrongstart = self::L2_WRONG_START;
        $l2rightend = self::L2_RIGHT_END;
        $l2wrongend = self::L2_WRONG_END;

        return [
                'L1=00 L2=00' => ['Line 1: ' . "$l1wrongstart $l1wrongend". ', Line 2: ' . "$l2wrongstart $l2wrongend",
                        ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"]],
                'L1=10 L2=00' => ['Line 1: ' . "$l1rightstart $l1wrongend". ', Line 2: ' . "$l2wrongstart $l2wrongend",
                        ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"]],
                'L1=11 L2=00' => ['Line 1: ' . "$l1rightstart $l1rightend". ', Line 2: ' . "$l2wrongstart $l2wrongend",
                        ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"]],
                'L1=10 L2=10' => ['Line 1: ' . "$l1rightstart $l1wrongend". ', Line 2: ' . "$l2rightstart $l2wrongend",
                        ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2wrongend"]],
                'L1=10 L2=01' => ['Line 1: ' . "$l1rightstart $l1wrongend". ', Line 2: ' . "$l2wrongstart $l2rightend",
                        ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2rightend"]],
                'L1=11 L2=10' => ['Line 1: ' . "$l1rightstart $l1rightend". ', Line 2: ' . "$l2rightstart $l2wrongend",
                        ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"]],
                'L1=11 L2=11' => ['Line 1: ' . "$l1rightstart $l1rightend". ', Line 2: ' . "$l2rightstart $l2rightend",
                        ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"]],
        ];
    }

    /**
     * Test the summarise_response function.
     *
     * @dataProvider summarise_response_provider
     * @param int|float $expected
     * @param array $responses
     * @return void
     */
    public function test_summarise_response(string $expected, array $response): void {
        $question = \test_question_maker::make_question('drawlines', 'mkmap_twolines');
        $question->start_attempt(new question_attempt_step(), 1);

        $actual = $question->summarise_response($response);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for methods taking question response {@see get_num_part_right_...}.
     *
     * @return array[]
     */
    public static function response_provider(): array {
        $l1rightstart = self::L1_RIGHT_START;
        $l1wrongstart = self::L1_WRONG_START;
        $l1rightend = self::L1_RIGHT_END;
        $l1wrongend = self::L1_WRONG_END;

        $l2rightstart = self::L2_RIGHT_START;
        $l2wrongstart = self::L2_WRONG_START;
        $l2rightend = self::L2_RIGHT_END;
        $l2wrongend = self::L2_WRONG_END;

        return [
            'part L1=00 L2=00' => [0, ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"], 'partial'],
            'part L1=10 L2=00' => [1, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"], 'partial'],
            'part L1=11 L2=00' => [2, ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"], 'partial'],
            'part L1=10 L2=10' => [2, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2wrongend"], 'partial'],
            'part L1=10 L2=01' => [2, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2rightend"], 'partial'],
            'part L1=11 L2=10' => [3, ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"], 'partial'],
            'part L1=11 L2=11' => [4, ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"], 'partial'],

            'all L1=00 L2=00' => [0, ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"], 'allnone'],
            'all L1=10 L2=00' => [0, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"], 'allnone'],
            'all L1=10 L2=10' => [0, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2wrongend"], 'allnone'],
            'all L1=10 L2=01' => [0, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2rightend"], 'allnone'],
            'all L1=10 L2=11' => [1, ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2rightend"], 'allnone'],
            'all L1=11 L2=10' => [1, ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"], 'allnone'],
            'all L1=11 L2=11' => [2, ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"], 'allnone'],
        ];
    }

    /**
     * Test the get_num_parts_right_grade_partial function.
     *
     * @dataProvider response_provider
     * @param int|float $expected
     * @param array $responses
     * @param string $grademethod
     * @return void
     */
    public function test_get_num_parts_right_grade_partial(int $expected, array $response, string $grademethod): void {
        if ($grademethod !== 'partial') {
            return;
        }

        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        [$numpartright, $total] = $question->get_num_parts_right_grade_partial($response);
        $this->assertEquals($expected, $numpartright);
        $this->assertEquals(4, $total);
    }

    /**
     * Test the get_num_parts_right_grade_allornone function.
     *
     * @dataProvider response_provider
     * @param int|float $expected
     * @param array $responses
     * @param string $grademethod
     * @return void
     */
    public function test_get_num_parts_right_grade_allornone(int|float $expected, array $response, string $grademethod): void {
        if ($grademethod !== 'allnone') {
            return;
        }

        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);

        [$numright, $total] = $question->get_num_parts_right_grade_allornone($response);
        $this->assertEquals($expected, $numright);
        $this->assertEquals(2, $total);
    }

    /**
     * Data provider for methods taking question response {@see grade_response..., ...}.
     *
     * @return array[]
     */
    public static function grade_response_provider(): array {
        $l1rightstart = self::L1_RIGHT_START;
        $l1wrongstart = self::L1_WRONG_START;
        $l1rightend = self::L1_RIGHT_END;
        $l1wrongend = self::L1_WRONG_END;

        $l2rightstart = self::L2_RIGHT_START;
        $l2wrongstart = self::L2_WRONG_START;
        $l2rightend = self::L2_RIGHT_END;
        $l2wrongend = self::L2_WRONG_END;

        return [
            'part L1=00 L2=00' => [
                [0, question_state::$gradedwrong],
                ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                'partial',
            ],
            'part L1=10 L2=00' => [
                [0.25, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                'partial',
            ],
            'part L1=11 L2=00' => [
                [0.50, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"],
                'partial',
            ],
            'part L1=10 L2=10' => [
                [0.50, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2wrongend"],
                'partial',
            ],
            'part L1=10 L2=01' => [
                [0.50, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2rightend"],
                'partial',
            ],
            'part L1=11 L2=10' => [
                [0.75, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"],
                'partial',
            ],
            'part L1=11 L2=11' => [
                [1, question_state::$gradedright],
                ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                'partial',
            ],

            'all L1=00 L2=00' => [
                [0, question_state::$gradedwrong],
                ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                'allnone',
            ],
            'all L1=10 L2=00' => [
                [0, question_state::$gradedwrong],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                'allnone',
            ],
            'all L1=11 L2=00' => [
                [0, question_state::$gradedwrong],
                ['c0' => "$l1wrongstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"],
                'allnone',
            ],
            'all L1=10 L2=10' => [
                [0, question_state::$gradedwrong],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2wrongend"],
                'allnone',
            ],
            'all L1=10 L2=11' => [
                [0.50, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2rightstart $l2rightend"],
                'allnone',
            ],
            'all L1=11 L2=10' => [
                [0.50, question_state::$gradedpartial],
                ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"],
                'allnone',
            ],
            'all L1=11 L2=11' => [
                [1, question_state::$gradedright],
                ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                'allnone',
            ],
        ];
    }

    /**
     * Test the grade_response function.
     *
     * @dataProvider grade_response_provider
     * @param array $expected A list of fraction an state (graderight, gradewrong, gradepartial).
     * @param array $responses
     * @param string $grademethod
     * @return void
     */
    public function test_grade_response(array $expected, array $response, string $grademethod): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->grademethod = $grademethod;
        $question->start_attempt(new question_attempt_step(), 1);
        $this->assertEquals($expected, $question->grade_response($response));
    }

    /**
     * Data provider for {@see test_compute_final_grade}.
     *
     * @return array[]
     */
    public static function compute_final_grade_provider(): array {
        $l1rightstart = self::L1_RIGHT_START;
        $l1wrongstart = self::L1_WRONG_START;
        $l1rightend = self::L1_RIGHT_END;
        $l1wrongend = self::L1_WRONG_END;

        $l2rightstart = self::L2_RIGHT_START;
        $l2wrongstart = self::L2_WRONG_START;
        $l2rightend = self::L2_RIGHT_END;
        $l2wrongend = self::L2_WRONG_END;

        return [
            // Single try.
            'L1=00 L2=00' => [0, ['1' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"]], 1],
            'L1=11 L2=00' => [0.50, ['1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"]], 1],
            'L1=01 L2=10' => [0.50, ['1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"]], 1],
            'L1=11 L2=01' => [0.75, ['1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2rightend"]], 1],
            'L1=11 L2=10' => [0.75, ['1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"]], 1],
            'L1=11 L2=11' => [1, ['1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"]], 1],

            // Multiple tries with penalties, totaltries set to 3.
            'T1: L1=00 L2=00, T2: L1=00 L2=00, T3: L1=11 L2=11' => [0.33334, [
                    '1' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '3' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=00 L2=00, T2: L1=10 L2=00, T3: L1=11 L2=11' => [0.41667, [
                    '1' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '3' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=00 L2=00, T2: L1=11 L2=00, T3: L1=11 L2=11' => [0.5, [
                    '1' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '3' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=10 L2=00, T2: L1=11 L2=10, T3: L1=11 L2=11' => [0.66667, [
                    '1' => ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"],
                    '3' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=00 L2=00, T2: L1=11 L2=11:' => [0.66667, [
                    '1' => ['c0' => "$l1wrongstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=10 L2=00, T2: L1=11 L2=11:' => [0.75, [
                    '1' => ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=11 L2=00, T2: L1=11 L2=11:' => [0.916667, [
                    '1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2wrongstart $l2rightend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=11 L2=11:' => [1, [
                    '1' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2rightend"],
                ], 3,
            ],
            'T1: L1=10 L2=00, T2: L1=11 L2=10, T3: L1=11 L2=10' => [0.41667, [
                    '1' => ['c0' => "$l1rightstart $l1wrongend", 'c1' => "$l2wrongstart $l2wrongend"],
                    '2' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"],
                    '3' => ['c0' => "$l1rightstart $l1rightend", 'c1' => "$l2rightstart $l2wrongend"],
                ], 3,
            ],
        ];
    }

    /**
     * Test the compute_final_grade function.
     *
     * @dataProvider compute_final_grade_provider
     * @param int|float $expected
     * @param array $responses
     * @param int $totaltries
     * @return void
     */
    public function test_compute_final_grade($expected, $responses, $totaltries): void {
        $question = \test_question_maker::make_question('drawlines');
        $question->start_attempt(new question_attempt_step(), 1);
        $fraction = $question->compute_final_grade($responses, $totaltries);
        if (is_float($fraction)) {
            $this->assertEqualsWithDelta($expected, $fraction, 0.00001);
        } else {
            $this->assertEquals($expected, $fraction);
        }
    }
}
