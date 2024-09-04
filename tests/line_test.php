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

use qtype_drawlines\line;

global $CFG;

require_once($CFG->dirroot . '/question/type/drawlines/classes/line.php');


/**
 * Unit tests for DrawLines question definition class.
 *
 * @package   qtype_drawlines
 * @copyright 2014 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qtype_drawlines\line
 */
class line_test extends \advanced_testcase {

    /**
     * Validate zone coordinates input format
     *
     * @dataProvider zone_coordinates_provider
     * @param string $zonecooredinate
     * @return bool
     */
    public function test_is_zone_coordinates_valid(string $zonecooredinate, bool $trueorfalse): void {
        $this->resetAfterTest();
        $this->assertEquals($trueorfalse, line::is_zone_coordinates_valid($zonecooredinate));
    }

    public function zone_coordinates_provider(): array {
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
     * Validate zone coordinates input format
     *
     * @dataProvider parse_into_cx_cy_radius_provider
     * @param string $zonecooredinates
     * @param array $coordslist
     * @param bool $radius
     * @return void
     */
    public function test_parse_into_cx_cy_with_or_without_radius(string $zonecooredinates, array $coordslist, bool $radius): void {
        $this->resetAfterTest();
        $this->assertEquals($coordslist, line::parse_into_cx_cy_with_or_without_radius($zonecooredinates, $radius));
    }
    public function parse_into_cx_cy_radius_provider(): array {
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
     * @param array $dropcoord
     * @param bool $radius
     * @return void
     */
    public function test_is_dragitem_in_the_right_place($dragcoord, $dropcoord, $radius) {
        $this->assertEquals($radius, Line::is_dragitem_in_the_right_place($dragcoord, $dropcoord));

    }
    public function is_dragitem_in_the_right_place_provider(): array {
        $dropcoord = '10,100;5'; // The correct coords and the given radius.
        return [
                'Exac match' => ['10,100', $dropcoord, true],
                'incorrect match x-6' => ['5,100', $dropcoord, false],
                'incorrect match x-6' => ['16,100', $dropcoord, false],
                'correct match x-5' => ['5,100', $dropcoord, true],
                'correct match x+5' => ['15,100', $dropcoord, true],

                'incorrect match y-6' => ['10,94', $dropcoord, false],
                'incorrect match y+6' => ['10,106', $dropcoord, false],
                'correct match y-5' => ['10,95', $dropcoord, true],
                'correct match y+5' => ['10,105', $dropcoord, true],
        ];
    }
}