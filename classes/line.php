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
use qtype_drawlines\dragitem;
use qtype_drawlines\dropzone;

/**
 * Represents a line objet of drawlines question.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class line {
    /** @var int Number of lines to start with. */
    const LINE_NUMBER_START = 1;

    /** @var int Number of lines to add. */
    const LINE_NUMBER_ADD = 2;

    /** @var string linesegment (Line segment ---). */
    const TYPE_LINE_SEGMENT = 'linesegment';

    /** @var string linesinglearrow (Single arrow -→). */
    const TYPE_LINE_SINGLE_ARROW = 'linesinglearrow';

    /** @var string linedoublearrows (Double arrows ←--→). */
    const TYPE_LINE_DOUBLE_ARROW = 'linedoublearrows';

    /** @var string lineinfinite (Infinite line --o--o--). */
    const TYPE_LINE_INFINITE = 'lineinfinite';


    /** @var string validate-zone-coordinates for start and the end of the line */
    const VALIDATE_ZONE_COORDINATES = "/^([^-][0-9]+),([^-][0-9]+);([^-][0-9]+)$/";

    /** @var string validate-response-coordinates for a line
     * as the start(scx,scy) and the end(ecx,ecy) coordinates of the line in 'scx,scy ecx,ecy' format.
     */
    const VALIDATE_RESPONSE_COORDINATES = "/^([^-][0-9]+),([^-][0-9]+)\b([^-][0-9]+),([^-][0-9]+)$/";

    /** @var int The line id. */
    public $id;

    /** @var int The id of the question. */
    public $questionid;

    /** @var int The line number. */
    public $number;

    /** @var string The line type. */
    public $type;

    /** @var string The label shows  at the start of the line. */
    public $labelstart;

    /** @var string The label shows in the middle of the line. */
    public $labelmiddle;

    /** @var string The label shows at the end of the line. */
    public $labelend;

    /** @var string The line start zone position in 'xcenter,ycenter;radius' format.*/
    public $zonestart;

    /** @var string The line end zone position in 'xcenter,ycenter;radius' format.*/
    public $zoneend;

    /**
     * Construct the line object.
     *
     * @param int $id
     * @param int $questionid
     * @param int $number
     * @param string $type
     * @param string $labelstart
     * @param string $labelmiddle
     * @param string $labelend
     * @param string $zonestart
     * @param string $zoneend
     */
    public function __construct(int $id, int $questionid, int $number, string $type,
            string $labelstart, string $labelmiddle, string $labelend,
            string $zonestart, string $zoneend) {

        $this->id = $id;
        $this->questionid = $questionid;
        $this->number = $number;
        $this->type = $type;
        $this->labelstart = $labelstart;
        $this->labelmiddle = $labelmiddle;
        $this->labelend = $labelend;
        $this->zonestart = $zonestart;
        $this->zoneend = $zoneend;
    }

    /**
     * Return an assosiative array of line types as key => value,
     * where key is stored name in the database and Value is the displayed name in question form.
     *
     * @return array
     */
    public static function get_line_types(): array {
        return [
            self::TYPE_LINE_SEGMENT => get_string(self::TYPE_LINE_SEGMENT, 'qtype_drawlines'),
            self::TYPE_LINE_SINGLE_ARROW => get_string(self::TYPE_LINE_SINGLE_ARROW , 'qtype_drawlines'),
            self::TYPE_LINE_DOUBLE_ARROW => get_string(self::TYPE_LINE_DOUBLE_ARROW, 'qtype_drawlines'),
            self::TYPE_LINE_INFINITE => get_string(self::TYPE_LINE_INFINITE, 'qtype_drawlines'),
        ];
    }

    /**
     * Return true or false
     *
     * @param $dragcoord string 'cx,cy' format
     * @param $dropcood string 'cx,cy;radius' format
     * @return bool
     */
    public static function is_dragitem_in_the_right_place($dragcoord, $dropcood): bool {
        [$cx, $cy, $r] = self::parse_into_cx_cy_with_or_without_radius($dropcood, true);
        [$rescx, $rescy] = self::parse_into_cx_cy_with_or_without_radius($dragcoord);

        $xcoord = false;
        if ((($cx - $r) <= $rescx) && ($rescx <= ($cx + $r))) {
            $xcoord = true;
        }
        $ycoord = false;
        if ((($cy - $r) <= $rescy) && ($rescy <= ($cy + $r))) {
            $ycoord = true;
        }
        if ($xcoord && $ycoord) {
            return true;
        }
        return false;
    }

    /**
     * Parse the input and return the parts in a list of 'cx', 'cy' with or whothout 'r'.
     *
     * @param string $dropzone, the string in a given format with or whithout radius
     * @param bool $radius, if set to true, return the list with radius, otherwise with radius
     * @return int[], a list of 'cx', 'cy' with or whothout 'r'.
     */
    public static function parse_into_cx_cy_with_or_without_radius(string $dropzone, bool $radius = false): array {
        if ($radius === true) {
            $dropzonecontent = explode(';', $dropzone);
            $coordinates = explode(',', $dropzonecontent[0]);

            // Return the parsts in a list of 'cx', 'cy' and 'r' in numbers.
            return [(int)$coordinates[0], (int)$coordinates[1], (int)$dropzonecontent[1]];
        }
        $coordinates = explode(',', $dropzone);
        // Return the parsts in a list of 'cx'and 'cy'in numbers.
        return [(int)$coordinates[0], (int)$coordinates[1]];
    }

    /**
     * Return the coordinates for a given dropzone (strat or end of the line).
     *
     * @param string $zone
     * @return string
     */
    public static function get_coordinates(string $zone): string {
        $zonestart = explode(';', $zone);
        return $zonestart[0];
    }

    /**
     * Return the radius for a given dropzone circle (strat or end of the line).
     *
     * @param string $zone
     * @return int
     */
    public static function get_radius(string $zone): int {
        $zonestart = explode(';', $zone);
        return (int)$zonestart[1];
    }

    /**
     * Validate the zone coordinates for Start or End zone of a line.
     * The correct format is x,y;r  where x,y are the coordinates of the centre of a circle and r is the radius.
     *
     * @param string $zone
     * @return bool
     */
    public static function is_zone_coordinates_valid(string $zone): bool {
        preg_match_all(self::VALIDATE_ZONE_COORDINATES, $zone, $matches, PREG_SPLIT_NO_EMPTY);
        // If the zone is empty return false.
        if (trim($zone) === '') {
            return false;
        }
        // If there is no match return false.
        foreach ($matches as $i => $match) {
            if (empty($match)) {
                return false;
            } else {
                break;
            }
        }
        // Match found.
        return true;
    }

    /**
     * Validate the response coordinates for Start or End zone of a line.
     * The correct format is 'scx,scy ecx,ecy' where scx,scy are the coordinates for
     * the start zone and ecx,ecy are the end zone coordinates of a line respectively.
     *
     * @param string $linecoordinates the coordinates for start and end of the line in 'scx,scy ecx,ecy' format.
     * @return bool
     */
    public static function are_response_coordinates_valid(string $linecoordinates): bool {
        // If the line-coordinates is empty return false.
        if (trim($linecoordinates) === '') {
            return false;
        }
        preg_match_all(self::VALIDATE_RESPONSE_COORDINATES, $linecoordinates, $matches, PREG_SPLIT_NO_EMPTY);

        // If there is no match return false.
        foreach ($matches as $i => $match) {
            if (empty($match)) {
                return false;
            }
        }
        // Match found.
        return true;
    }


    public static function make_drop_zone(int $linenumber, string $label, string $zone): drop_zone {
        [$xleft, $ytop] = self::parse_into_cx_cy_with_or_without_radius($zone);
        return  new drop_zone($linenumber, $label, $xleft, $ytop);
    }

}

/**
 * Represents a drop-zone objet for start or end of a line.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drop_zone {
    /** @var int The number of the line */
    public $linenumber;

    /** @var string Alt text for the drop zone item */
    public $label;

    /** @var array X and Y location of the drop zone */
    public $xy;

    /**
     * Create a drop zone object.
     *
     * @param int $linenumber Which line the drop zone belong to
     * @param string $alttextlabel The alt text of the drop zone
     * @param int $x X location
     * @param int $y Y location
     */
    public function __construct($linenumber, $label, $x, $y) {
        $this->linenumber = $linenumber;
        $this->label = $label;
        $this->xy = [$x, $y];
    }

    /**
     * Returns the line number for this droo zone.
     *
     */
    public function get_linenumber(): int {
        return $this->linenumber;
    }
}
