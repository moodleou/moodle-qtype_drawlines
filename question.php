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

use qtype_drawlines\line;

/**
 * Draw lines question definition class.
 *
 * @package    qtype_drawlines
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_drawlines_question extends question_graded_automatically {

    /** @var string feedback for any correct response. */
    public string $correctfeedback;

    /** @var int format of $correctfeedback. */
    public int $correctfeedbackformat;

    /** @var string feedback for any partially correct response. */
    public string $partiallycorrectfeedback;

    /** @var int format of $partiallycorrectfeedback. */
    public int $partiallycorrectfeedbackformat;

    /** @var string feedback for any incorrect response. */
    public string $incorrectfeedback;

    /** @var int format of $incorrectfeedback. */
    public int $incorrectfeedbackformat;

    /** @var int shows if the drag lines are misplaced. */
    public $showmisplaced;

    /** @var string 'allnone' (All-or-nothing) or 'partial' (Give partial credit) grading method. */
    public string $grademethod;

    /** @var line[], an array of line objects. */
    public $lines;

    /** @var int The number of lines. */
    public $numberoflines;

    #[\Override]
    public function get_expected_data() {
        $expecteddata = [];
        foreach ($this->lines as $line) {
            $expecteddata[$this->choice($line->number - 1)] = PARAM_RAW;
        }
        return $expecteddata;
    }

    #[\Override]
    public function is_complete_response(array $response): bool {
        // If there is no response return false.
        if (empty($response)) {
            return false;
        }
        // If there is no response for each line return false for all-or-nothing grading method.
        if (count($response) < $this->numberoflines) {
            return false;
        }
        foreach ($this->lines as $key => $line) {
            if (isset($response[$this->choice($key)]) &&
                    !line::are_response_coordinates_valid($response[$this->choice($key)])) {
                return false;
            }
        }
        return true;
    }

    #[\Override]
    public function get_correct_response() {
        $response = [];
        foreach ($this->lines as $key => $line) {
            $response[$this->choice($key)] = line::get_coordinates($line->zonestart) . ' '
                    . line::get_coordinates($line->zoneend);
        }
        return $response;
    }

    #[\Override]
    public function summarise_response(array $response): ?string {
        $responsewords = [];
        $answers = [];
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->choice($key), $response) && $response[$this->choice($key)] != '') {
                $coordinates = explode(' ', $response[$this->choice($key)]);
                if ($line->type == 'lineinfinite' && count($coordinates) == 4) {
                    $coordinates = explode(' ', $response[$this->choice($key)]);
                    $answers[] = 'Line ' . $line->number . ': ' . $coordinates[1] . ' ' . $coordinates[2];
                    continue;
                }
                $answers[] = 'Line ' . $line->number . ': ' . $response[$this->choice($key)];
            }
        }
        if (count($answers) > 0) {
            $responsewords[] = implode(', ', $answers);
        }
        return implode('; ', $responsewords);
    }

    #[\Override]
    public function is_gradable_response(array $response) {
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->choice($key), $response)) {
                return true;
            }
        }
        return false;
    }

    #[\Override]
    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->lines as $key => $line) {
            $fieldname = $this->choice($key);
            if (!question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, $fieldname)) {
                return false;
            }
        }
        return true;
    }

    #[\Override]
    public function grade_response(array $response): array {
        // Retrieve the number of right responses and the total number of responses.
        if ($this->grademethod == 'partial') {
            [$numright, $total] = $this->get_num_parts_right_grade_partialt($response);
        } else {
            [$numright, $total] = $this->get_num_parts_right_grade_allornone($response);
        }
        $fraction = $numright / $total;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    /**
     * Get the number of correct choices selected in the response, for 'Give partial credit' grade method.
     *
     * @param array $response The response list.
     * @return array The array of number of correct lines (start, end or both points of lines).
     */
    public function get_num_parts_right_grade_partialt(array $response): array {
        if (!$response) {
            return [0, 0];
        }
        $numpartrightstart = 0;
        $numpartrightend = 0;
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->choice($key), $response) && $response[$this->choice($key)] !== '') {
                $coords = explode(' ', $response[$this->choice($key)]);
                if (line::is_dragitem_in_the_right_place($coords[0], $line->zonestart)) {
                    $numpartrightstart++;
                }
                if (line::is_dragitem_in_the_right_place($coords[1], $line->zoneend)) {
                    $numpartrightend++;
                }
            }
        }
        $numpartright = $numpartrightstart + $numpartrightend;
        $total = count($this->lines) * 2;
        return [$numpartright, $total];
    }

    /**
     * Get the number of correct choices selected in the response, for All-or-nothing grade method.
     *
     * @param array $response The response list.
     * @return array The array of number of correct lines (both start and end points).
     */
    public function get_num_parts_right_grade_allornone(array $response): array {
        if (!$response) {
            return [0, 0];
        }
        $numright = 0;
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->choice($key), $response) && $response[$this->choice($key)] !== '') {
                $coords = explode(' ', $response[$this->choice($key)]);
                if (line::is_dragitem_in_the_right_place($coords[0], $line->zonestart) &&
                        line::is_dragitem_in_the_right_place($coords[1], $line->zoneend)) {
                    $numright++;
                }
            }
        }
        $total = count($this->lines);
        return [$numright, $total];
    }

    #[\Override]
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($filearea === 'bgimage') {
            $validfilearea = true;
        } else {
            $validfilearea = false;
        }
        if ($component === 'qtype_drawlines' && $validfilearea) {
            $question = $qa->get_question(false);
            $itemid = reset($args);
            return $itemid == $question->id;
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }

    #[\Override]
    public function get_validation_error(array $response): string {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleasedragalllines', 'qtype_drawlines');
    }

    /**
     * Compute the distance from the point ($x, $y) to the line through the two points ($x1, $y1) and ($x2, $y2).
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param float $x
     * @param float $y
     * @return float distance from the point ($x, $y) to the line through points ($x1, $y1), ($x2, $y2).
     */
    public function compute_distance_to_line(float $x1, float $y1, float $x2, float $y2, float $x, float $y): float {
        return sqrt(($x - $x1) ** 2 + ($y - $y1) ** 2 -
                (($x2 - $x1) * ($x - $x1) + ($y2 - $y1) * ($y - $y1)) ** 2 / (($x2 - $x1) ** 2 + ($y2 - $y1) ** 2));
    }

    #[\Override]
    public function classify_response(array $response) {
        $classifiedresponse = [];
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->choice($key), $response) && $response[$this->choice($key)] !== '') {
                if ($this->grademethod == 'partial') {
                    $fraction = 0.5;
                } else {
                    $fraction = 1;
                }
                $classifiedresponse[$key] = new question_classified_response(
                        $line->number,
                        'Line ' . $line->number . ': ' . $response[$this->choice($key)],
                        $fraction);
            } else {
                $classifiedresponse[$key] = question_classified_response::no_response();
            }
        }
        return $classifiedresponse;
    }

    /**
     * Work out a final grade for this attempt, taking into account
     * all the tries the student made and return the grade value.
     *
     * @param array $responses the response for each try. Each element of this
     * array is a response array, as would be passed to {@link grade_response()}.
     * There may be between 1 and $totaltries responses.
     *
     * @param int $totaltries The maximum number of tries allowed.
     *
     * @return float the fraction that should be awarded for this
     * sequence of response.
     */
    public function compute_final_grade(array $responses, int $totaltries): float {
        // TODO: To incorporate the question penalty for interactive with multiple tries behaviour.

        $grade = 0;
        foreach ($responses as $response) {
            [$fraction, $state] = $this->grade_response($response);
            $grade += $fraction;
        }
        return $grade;
    }

    /**
     * Get a choice identifier
     *
     * @param int $choice stem number
     * @return string the question-type variable name.
     */
    public function choice($choice) {
        return 'c' . $choice;
    }
}
