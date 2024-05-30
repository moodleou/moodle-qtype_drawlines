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
    const TYPE_LINE_SEGMENT ='linesegment';

    /** @var string linesinglearrow (Single arrow -→). */
    const TYPE_LINE_SINGLE_ARROW ='linesinglearrow';

    /** @var string linedoublearrows (Double arrows ←--→). */
    const TYPE_LINE_DOUBLE_ARROW ='linedoublearrows';

    /** @var string lineinfinite (Infinite line --o--o--). */
    const TYPE_LINE_INFINITE ='lineinfinite';


    /** @var string lineinfinite (Infinite line --o--o--). */
    const VALIDATE_ZONE_COORDINATES = "/([0-9]+),([0-9]+);([0-9]+)/";

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
     * Validate the zone coordinates for Start or End zone of a line.
     * The correct format is x,y;r  where x,y are the coordinates of the centre of a circle and r is the radius.
     *
     * @param string $zone
     * @return bool
     */
    public static function is_zone_coordinates_valid(string $zone): bool {
        preg_match_all(self::VALIDATE_ZONE_COORDINATES, $zone, $matches);
        // If the zone is empty return fale
        if (trim($zone) === '') {
            return false;
        }
        // if there is no match return false.
        foreach ($matches as $i => $match) {
            if (empty($matches[$i])) {
                return false;
            }
        }
        // Match found.
        return true;
    }
}
