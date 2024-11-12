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
    public function is_gradable_response(array $response) {
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
                $coords = explode(' ', $response[$this->field($key)]);
                if ($line->type == 'lineinfinite') {
                    if (count($coords) == 2) {
                        // Response with 2 coordinates (x1,y1 x2,y2).
                        if (line::is_item_positioned_correctly_on_axis(
                                $coords[0], $line->zonestart, $line->zoneend, 'start')) {
                            $numpartright++;
                        }
                        if (line::is_item_positioned_correctly_on_axis(
                                $coords[1], $line->zonestart, $line->zoneend, 'end')) {
                            $numpartright++;
                        }
                    } else {
                        // Response has 4 coordinates(x1,y1 x2,y2 x3,y3 x4,y4).
                        // Here we need to consider x2,y2 x3,y3 for calculation.
                        if (line::is_item_positioned_correctly_on_axis(
                                $coords[1], $line->zonestart, $line->zoneend, 'start')) {
                            $numpartright++;
                        }
                        if (line::is_item_positioned_correctly_on_axis(
                                $coords[2], $line->zonestart, $line->zoneend, 'end')) {
                            $numpartright++;
                        }
                    }
                } else {
                    $numpartrightstart = 0;
                    $numpartrightend = 0;
                    if (line::is_dragitem_in_the_right_place($coords[0], $line->zonestart)) {
                        $numpartrightstart++;
                    }
                    if (line::is_dragitem_in_the_right_place($coords[1], $line->zoneend)) {
                        $numpartrightend++;
                    }
                    $numpartright += $numpartrightstart + $numpartrightend;
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
                $coords = explode(' ', $response[$this->field($key)]);
                if ($line->type == 'lineinfinite') {
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
                    if ($isstartrightplace && $isendrightplace) {
                        $numright++;
                    }
                } else {
                    if (line::is_dragitem_in_the_right_place($coords[0], $line->zonestart) &&
                            line::is_dragitem_in_the_right_place($coords[1], $line->zoneend)) {
                        $numright++;
                    }
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

    #[\Override]
    public function classify_response(array $response) {
        $classifiedresponse = [];
        foreach ($this->lines as $key => $line) {
            if (array_key_exists($this->field($key), $response) && $response[$this->field($key)] !== '') {
                if ($this->grademethod == 'partial') {
                    $fraction = 0.5;
                } else {
                    $fraction = 1;
                }
                $classifiedresponse[$key] = new question_classified_response(
                        $line->number,
                        'Line ' . $line->number . ': ' . $response[$this->field($key)],
                        $fraction);
            } else {
                $classifiedresponse[$key] = question_classified_response::no_response();
            }
        }
        return $classifiedresponse;
    }

    #[\Override]
    public function grade_response(array $response): array {
        // Retrieve the number of right responses and the total number of responses.
        [$numright, $numtotal] = $this->retrieve_numright_numtotal($response);
        $fraction = $numright / $numtotal;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    /**
     * Return number of correct responses and the total numbe of answers.
     *
     * @param array $response The respnse array
     * @return array|int[] The array containing number of correct responses and the total.
     */
    public function retrieve_numright_numtotal(array $response): array {
        // Retrieve the number of right responses and the total number of responses.
        if ($this->grademethod == 'partial') {
            [$numright, $numtotal] = $this->get_num_parts_right_grade_partial($response);
        } else {
            [$numright, $numtotal] = $this->get_num_parts_right_grade_allornone($response);
        }
        return [$numright, $numtotal];
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
    public function compute_final_grade(array $responses, int $totaltries): int|float {
        $grade = 0;
        if ($totaltries === 1) {
            foreach ($responses as $key => $response) {
                [$fraction, $state] = $this->grade_response($response);
                $grade += $fraction;
            }
            return $grade;
        }
        $reversedresponses = array_reverse($responses, true);
        for ($tr = $totaltries; $tr >= 1; $tr--) {
            $response = $reversedresponses[$tr];
            [$fraction, $state] = $this->grade_response($response);

            // Apply penalties.
            [$numright, $numtotal] = $this->retrieve_numright_numtotal($response);
            $penalty = ($numtotal - $numright) * ($this->penalty / $numtotal);
            $grade += $fraction - $penalty;
        }
        $finalgrade = $grade / $totaltries;
        return $finalgrade;
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
