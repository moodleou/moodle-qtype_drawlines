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
            $expecteddata[$this->field($line->number - 1)] = PARAM_RAW;
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
        if (count($response) < count($this->lines)) {
            return false;
        }
        foreach ($this->lines as $key => $line) {
            if (isset($response[$this->field($key)]) &&
                    !line::are_response_coordinates_valid($response[$this->field($key)], $line->type)) {
                return false;
            }
        }
        return true;
    }

    #[\Override]
    public function get_correct_response() {
        $response = [];
        foreach ($this->lines as $key => $line) {
            $response[$this->field($key)] = line::get_coordinates($line->zonestart) . ' '
                    . line::get_coordinates($line->zoneend);
        }
        return $response;
    }

    #[\Override]
    public function summarise_response(array $response): ?string {
        $responsewords = [];
        $answers = [];
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)] != '') {
                $coordinates = explode(' ', $response[$this->field($key)]);
                if ($line->type == 'lineinfinite' && count($coordinates) == 4) {
                    $coordinates = explode(' ', $response[$this->field($key)]);
                    $answers[] = 'Line ' . $line->number . ': ' . $coordinates[1] . ' ' . $coordinates[2];
                    continue;
                }
                $answers[] = 'Line ' . $line->number . ': ' . $response[$this->field($key)];
            }
        }
        if (count($answers) > 0) {
            $responsewords[] = implode(', ', $answers);
        }
        return implode('; ', $responsewords);
    }

    #[\Override]
    public function is_gradable_response(array $response): bool {
        if (!isset($response)) {
            return false;
        }
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->field($key), $response)) {
                return true;
            }
        }
        return false;
    }

    #[\Override]
    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->lines as $key => $line) {
            $fieldname = $this->field($key);
            if (!question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, $fieldname)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the number of correct choices selected in the response, for 'Give partial credit' grade method.
     *
     * @param array $response The response list.
     * @return array The array of number of correct lines (start, end or both points of lines).
     */
    public function get_num_parts_right_grade_partial(array $response): array {
        if (!$response) {
            return [0, 0];
        }
        $numpartright = 0;
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)] !== '') {
                [$isstartrightplace, $isendrightplace] = $this->is_line_correctly_placed($response[$this->field($key)], $key);
                if ($isstartrightplace) {
                    $numpartright++;
                }
                if ($isendrightplace) {
                    $numpartright++;
                }
            }
        }
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
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)] !== '') {
                [$isstartrightplace, $isendrightplace] = $this->is_line_correctly_placed($response[$this->field($key)], $key);
                if ($isstartrightplace && $isendrightplace) {
                    $numright++;
                }
            }
        }
        $total = count($this->lines);
        return [$numright, $total];
    }

    /**
     * Get the number of correct choices selected in the response.
     *
     * @param string $response The response string.
     * @param int $key the question line number to compare with.
     * @return array Returns an array of bools true if the line end points are correctly answered.
     */
    public function is_line_correctly_placed(string $response, $key): array {
        $coords = explode(' ', $response);
        $line = $this->lines[$key];
        if ($line->type == line::TYPE_LINE_INFINITE) {
            if (count($coords) == 2) {
                // Response with 2 coordinates (x1,y1 x2,y2 x3,y3 x4,y4).
                $isstartrightplace = line::is_item_positioned_correctly_on_axis(
                        $coords[0], $line->zonestart, $line->zoneend, 'start'
                );
                $isendrightplace = line::is_item_positioned_correctly_on_axis(
                        $coords[1], $line->zonestart, $line->zoneend, 'end'
                );
            } else {
                // Response has 4 coordinates(x1,y1 x2,y2 x3,y3 x4,y4).
                // Here we need to consider x2,y2 x3,y3 for calculation.
                $isstartrightplace = line::is_item_positioned_correctly_on_axis(
                        $coords[1], $line->zonestart, $line->zoneend, 'start'
                );
                $isendrightplace = line::is_item_positioned_correctly_on_axis(
                        $coords[2], $line->zonestart, $line->zoneend, 'end'
                );
            }
        } else {
            $isstartrightplace = line::is_dragitem_in_the_right_place($coords[0], $line->zonestart);
            $isendrightplace = line::is_dragitem_in_the_right_place($coords[1], $line->zoneend);
        }
        return [$isstartrightplace, $isendrightplace];
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

    #[\Override]
    public function classify_response(array $response) {
        if ($this->grademethod == 'partial') {
            $partialcredit = 0.5;
        } else {
            $partialcredit = 0;
        }
        foreach ($this->lines as $key => $line) {
            $quelinecoordsstart = explode(';', $line->zonestart);
            $quelinecoordsend = explode(';', $line->zoneend);
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)] !== '') {
                [$startcoordsmatched, $endcoordsmatched] = $this->is_line_correctly_placed($response[$this->field($key)], $key);
                if ($startcoordsmatched && $endcoordsmatched) {
                    $classifiedresponse['Line ' . $line->number . ' (' . $quelinecoordsstart[0] . ') (' .
                            $quelinecoordsend[0] . ')'] =
                        new question_classified_response(1, get_string('valid_startandendcoordinates', 'qtype_drawlines'), 1);
                } else if ($startcoordsmatched) {
                    $classifiedresponse['Line ' . $line->number . ' (' . $quelinecoordsstart[0] . ')'] =
                        new question_classified_response(2, get_string('valid_startcoordinates', 'qtype_drawlines'),
                                $partialcredit);
                } else if ($endcoordsmatched) {
                    $classifiedresponse['Line ' . $line->number . ' (' . $quelinecoordsend[0] . ')'] =
                        new question_classified_response(3, get_string('valid_endcoordinates', 'qtype_drawlines'), $partialcredit);
                } else {
                    $classifiedresponse['Line ' . $line->number] =
                        new question_classified_response(4, get_string('incorrectresponse', 'qtype_drawlines'), 0);
                }
            } else {
                $classifiedresponse['Line ' . $line->number] = question_classified_response::no_response();
            }
        }
        return $classifiedresponse;
    }

    #[\Override]
    public function prepare_simulated_post_data($simulatedresponse): array {
        $postdata = [];
        foreach ($this->lines as $key => $line) {
            if (isset($simulatedresponse[$key])) {
                $postdata[$this->field($key)] = $simulatedresponse[$key];
            }
        }
        return $postdata;
    }

    #[\Override]
    public function grade_response(array $response): array {
        // Retrieve the number of right responses and the total number of responses.
        if ($this->grademethod == 'partial') {
            [$numright, $numtotal] = $this->get_num_parts_right_grade_partial($response);
        } else {
            [$numright, $numtotal] = $this->get_num_parts_right_grade_allornone($response);
        }
        $fraction = $numright / $numtotal;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
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
     * sequence of responses.
     */
    public function compute_final_grade(array $responses, int $totaltries): int|float {
        $penalties = 0;
        $grade = 0;
        foreach ($responses as $response) {
            [$fraction, $state] = $this->grade_response($response);
            if ($state->is_graded() === true) {
                if ($totaltries === 1) {
                    return $fraction;
                }
                $grade = max(0, $fraction - $penalties);
                if ($state->get_feedback_class() === 'correct') {
                    return $grade;
                }
                if ($state->get_feedback_class() === 'incorrect') {
                    $penalties += $this->penalty;
                }
                if ($state->get_feedback_class() === 'partiallycorrect') {
                    if ($this->grademethod == 'partial') {
                        [$trynumright, $numtotal] = $this->get_num_parts_right_grade_partial($response);
                    } else {
                        [$trynumright, $numtotal] = $this->get_num_parts_right_grade_allornone($response);
                    }
                    $partpenaly = (($numtotal - $trynumright) * $this->penalty / $numtotal);
                    $penalties += min($this->penalty, $partpenaly);
                }
            }
        }
        return $grade;
    }

    /**
     * Get a choice index identifier
     *
     * @param int $choice
     * @return string the question-type variable name.
     */
    public function field($choice): string {
        return 'c' . $choice;
    }
}
