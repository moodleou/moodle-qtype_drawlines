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

/**
 * Unit tests for draw lines question definition class.
 *
 * @package   qtype_drawlines
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \qtype_drawlines\line
 */
final class line_test extends \advanced_testcase {

    /**
     * Validate zone coordinates input format.
     *
     * @dataProvider zone_coordinates_provider
     * @param string $zonecooredinate
     * @return bool
     */
    public function test_is_zone_coordinates_valid(string $zonecooredinate, bool $trueorfalse): void {
        $this->assertEquals($trueorfalse, line::is_zone_coordinates_valid($zonecooredinate));
    }

    /**
     * Data provider for {@see test_is_zone_coordinates_valid}.
     *
     * @return array[]
     */
    public static function zone_coordinates_provider(): array {
        return [
                '10,100;15 is in correct format' => ['10,100;15', true],
                '10,100=14 is not in correct format' => ['150,100=14', false],
                'radius is missing' => ['10,100;', false],
                'radius must be a anumber' => ['10,100;r', false],
                'xcenter is missing' => [',150;12', false],
                'ycenter is missing' => ['200,;12', false],
                'numbers must be positive' => ['-200,150;12', false],
                'zone coordinate is valid' => ['300,200;12', true],
        ];
    }

    /**
     * Validate zone coordinates input format.
     *
     * @dataProvider parse_into_cx_cy_radius_provider
     * @param string $zonecooredinates
     * @param array $coordslist
     * @param bool $radius
     * @return void
     */
    public function test_parse_into_cx_cy_with_or_without_radius(string $zonecooredinates, array $coordslist, bool $radius): void {
        $this->assertEquals($coordslist, line::parse_into_cx_cy_with_or_without_radius($zonecooredinates, $radius));
    }

    /**
     * Data provider for {@see test_parse_into_cx_cy_with_or_without_radius}.
     *
     * @return array[]
     */
    public static function parse_into_cx_cy_radius_provider(): array {
        return [
                'Coords with radius' => ['10,100;15', [10, 100, 15], true],
                'Coords without radiust' => ['10,100', [10, 100], false],
        ];
    }

    /**
     * Validate zone coordinates input format
     *
     * @dataProvider is_dragitem_in_the_right_place_provider
     * @param string $dragcoord
     * @param string $dropcoord
     * @param bool $radius
     * @return void
     */
    public function test_is_dragitem_in_the_right_place($dragcoord, $dropcoord, $radius): void {
        $this->assertEquals($radius, line::is_dragitem_in_the_right_place($dragcoord, $dropcoord));
    }

    /**
     * Data provider for {@see test_is_dragitem_in_the_right_place}.
     *
     * @return array[]
     */
    public static function is_dragitem_in_the_right_place_provider(): array {
        $dropcoord = '10,100;5'; // The correct coords and the given radius.
        return [
                'Exac match' => ['10,100', $dropcoord, true],
                'incorrect match x-6' => ['4,100', $dropcoord, false],
                'incorrect match x+6' => ['16,100', $dropcoord, false],
                'correct match x-5' => ['5,100', $dropcoord, true],
                'correct match x+5' => ['15,100', $dropcoord, true],

                'incorrect match y-6' => ['10,94', $dropcoord, false],
                'incorrect match y+6' => ['10,106', $dropcoord, false],
                'correct match y-5' => ['10,95', $dropcoord, true],
                'correct match y+5' => ['10,105', $dropcoord, true],
        ];
    }

    /**
     * Validate zone coordinates input for infinite line
     *
     * @dataProvider is_item_positioned_correctly_on_axis_provider
     * @param string $dragcoord
     * @param string $linestartcoord
     * @param string $lineendcoord
     * @param string $which
     * @param bool $expected
     * @return void
     */
    public function test_is_item_positioned_correctly_on_axis($dragcoord, $linestartcoord, $lineendcoord, $which, $expected): void {
        $this->assertEquals($expected, line::is_item_positioned_correctly_on_axis(
                $dragcoord, $linestartcoord, $lineendcoord, $which
        ));
    }

    /**
     * Data provider for {@see test_is_item_positioned_correctly_on_axis}.
     *
     * @return array[]
     */
    public static function is_item_positioned_correctly_on_axis_provider(): array {
        $linestartcoord = '10,100;5'; // The correct coords and the given radius.
        $lineendcoord = '100,200;10'; // The correct coords and the given radius.
        return [
                'start exact match' => ['10,100', $linestartcoord, $lineendcoord, 'start', true],
                'start incorrect match x-10' => ['0,100', $linestartcoord, $lineendcoord, 'start', false],
                'start incorrect match x+10' => ['20,100', $linestartcoord, $lineendcoord, 'start', false],
                'start correct match x-5' => ['5,100', $linestartcoord, $lineendcoord, 'start', true],
                'start correct match x+5' => ['15,100', $linestartcoord, $lineendcoord, 'start', true],

                'start incorrect match y-10' => ['10,90', $linestartcoord, $lineendcoord, 'start', false],
                'start incorrect match y+10' => ['10,110', $linestartcoord, $lineendcoord, 'start', false],
                'start correct match y-5' => ['10,95', $linestartcoord, $lineendcoord, 'start', true],
                'start correct match y+5' => ['10,105', $linestartcoord, $lineendcoord, 'start', true],

                'end exact match' => ['100,200', $linestartcoord, $lineendcoord, 'end', true],
                'end incorrect match x-15' => ['85,200', $linestartcoord, $lineendcoord, 'end', false],
                'end incorrect match' => ['110,190', $linestartcoord, $lineendcoord, 'end', false],
                'end correct match y-10' => ['100,190', $linestartcoord, $lineendcoord, 'end', true],
                'end correct match' => ['105,195', $linestartcoord, $lineendcoord, 'end', true],
        ];
    }

    /**
     * Test compute_distance_to_line.
     *
     * @dataProvider compute_distance_to_line_testcases
     *
     * @param float $expecteddistance
     * @param float[] $p1 the point ($x1, $y1)
     * @param float[] $p2 the point ($x2, $y2)
     * @param float[] $p the point ($x, $y)
     */
    public function test_compute_distance_to_line(float $expecteddistance, array $p1, array $p2, array $p): void {
        [$x1, $y1] = $p1;
        [$x2, $y2] = $p2;
        [$x, $y] = $p;
        $this->assertEqualsWithDelta(
                $expecteddistance,
                line::compute_distance_to_line($x1, $y1, $x2, $y2, $x, $y),
                1e-10,
        );
    }

    /**
     * Data provider for {@see test_compute_distance_to_line}.
     *
     * @return array[]
     */
    public static function compute_distance_to_line_testcases(): array {
        return [
                '(x, y) is p1' => [0, [0, 0], [1, 1], [0, 0]],
                '(x, y) is p2' => [0, [0, 0], [1, 1], [1, 1]],
                '(x, y) is on the line beyond p1' => [0, [0, 0], [1, 1], [-2, -2]],
                '(x, y) is on the line in the middle' => [0, [0, 0], [2, 2], [1, 1]],
                '(x, y) is on the line beyond p2' => [0, [0, 0], [1, 1], [5, 5]],
                '(x, y) is orthogonal to p1' => [5, [0, 0], [1, 0], [0, 5]],
                '(x, y) is orthogonal to p2' => [2, [0, -10], [0, 0], [-2, 0]],
                '(x, y) is orthogonal to the midpoint' => [2, [0, -10], [0, 10], [-2, 0]],
                '45deg diagonal case' => [sqrt(2), [0, -2], [2, 0], [0, 0]],
                'diagonal case' => [12, [0, 15], [20, 0], [0, 0]],
                'diagonal case flipped' => [12, [20, 0], [0, 15], [0, 0]],
                'diagonal case flipped other way' => [12, [0, 15], [20, 0], [20, 15]],
        ];
    }
}
