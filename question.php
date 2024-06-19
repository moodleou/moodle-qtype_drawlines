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
#[AllowDynamicProperties]
class qtype_drawlines_question extends question_graded_automatically_with_countback {

    /** @var lines[], an array of line objects. */
    public $lines;

    /** @var int The number of lines. */
    public $numberoflines;

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

    /**
     * Get a choice identifier
     *
     * @param int $choice stem number
     * @return string the question-type variable name.
     */
    public function choice($choice) {
        return 'c' . $choice;
    }

    #[\Override]
    public function get_expected_data() {
        $vars = [];
        foreach ($this->choices[1] as $choice => $notused) {
            $vars[$this->choice($choice)] = PARAM_NOTAGS;
        }
        return $vars;
    }

    #[\Override]
    public function is_complete_response(array $response) {
        foreach ($this->choices[1] as $choiceno => $notused) {
            if (isset($response[$this->choice($choiceno)]) &&
                    trim($response[$this->choice($choiceno) != ''])) {
                return true;
            }
        }
        return false;
    }

    #[\Override]
    public function is_gradable_response(array $response) {
        return $this->is_complete_response($response);
    }

    #[\Override]
    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->choices[1] as $choice => $notused) {
            $fieldname = $this->choice($choice);
            if (!$this->arrays_same_at_key_integer(
                    $prevresponse, $newresponse, $fieldname)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tests to see whether two arrays have the same set of coords at a particular key. Coords
     * can be in any order.
     * @param array $array1 the first array.
     * @param array $array2 the second array.
     * @param string $key an array key.
     * @return bool whether the two arrays have the same set of coords (or lack of them)
     * for a given key.
     */
    public function arrays_same_at_key_integer(
            array $array1, array $array2, $key) {
        if (array_key_exists($key, $array1)) {
            $value1 = $array1[$key];
        } else {
            $value1 = '';
        }
        if (array_key_exists($key, $array2)) {
            $value2 = $array2[$key];
        } else {
            $value2 = '';
        }
        $coords1 = explode(';', $value1);
        $coords2 = explode(';', $value2);
        if (count($coords1) !== count($coords2)) {
            return false;
        } else if (count($coords1) === 0) {
            return true;
        } else {
            $valuesinbotharrays = $this->array_intersect_fixed($coords1, $coords2);
            return (count($valuesinbotharrays) == count($coords1));
        }
    }

    #[\Override]
    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleasedragatleastonemarker', 'qtype_drawlines');
    }

    #[\Override]
    public function get_num_parts_right(array $response) {
        $chosenhits = $this->choose_hits($response);
        $divisor = max(count($this->rightchoices), $this->total_number_of_items_dragged($response));
        return [count($chosenhits), $divisor];
    }

    /**
     * Choose hits to maximize grade where drop targets may have more than one hit and drop targets
     * can overlap.
     * @param array $response
     * @return array chosen hits
     */
    protected function choose_hits(array $response) {
        $allhits = $this->get_all_hits($response);
        $chosenhits = [];
        foreach ($allhits as $placeno => $hits) {
            foreach ($hits as $itemno => $hit) {
                $choice = $this->get_right_choice_for($placeno);
                $choiceitem = "$choice $itemno";
                if (!in_array($choiceitem, $chosenhits)) {
                    $chosenhits[$placeno] = $choiceitem;
                    break;
                }
            }
        }
        return $chosenhits;
    }

    #[\Override]
    public function total_number_of_items_dragged(array $response) {
        $total = 0;
        foreach ($this->choiceorder[1] as $choice) {
            $choicekey = $this->choice($choice);
            if (array_key_exists($choicekey, $response) && trim($response[$choicekey] !== '')) {
                $total += count(explode(';', $response[$choicekey]));
            }
        }
        return $total;
    }

    /**
     * Get's an array of all hits on drop targets. Needs further processing to find which hits
     * to select in the general case that drop targets may have more than one hit and drop targets
     * can overlap.
     * @param array $response
     * @return array all hits
     */
    protected function get_all_hits(array $response) {
        $hits = [];
        foreach ($this->places as $placeno => $place) {
            $rightchoice = $this->get_right_choice_for($placeno);
            $rightchoicekey = $this->choice($rightchoice);
            if (!array_key_exists($rightchoicekey, $response)) {
                continue;
            }
            $choicecoords = $response[$rightchoicekey];
            $coords = explode(';', $choicecoords);
            foreach ($coords as $itemno => $coord) {
                if (trim($coord) === '') {
                    continue;
                }
                $pointxy = explode(',', $coord);
                $pointxy[0] = round($pointxy[0]);
                $pointxy[1] = round($pointxy[1]);
                if ($place->drop_hit($pointxy)) {
                    if (!isset($hits[$placeno])) {
                        $hits[$placeno] = [];
                    }
                    $hits[$placeno][$itemno] = $coord;
                }
            }
        }
        // Reverse sort in order of number of hits per place (if two or more
        // hits per place then we want to make sure hits do not hit elsewhere).
        $sortcomparison = function ($a1, $a2){
            return (count($a1) - count($a2));
        };
        uasort($hits, $sortcomparison);
        return $hits;
    }

    #[\Override]
    public function get_right_choice_for($place) {
        $group = $this->places[$place]->group;
        foreach ($this->choiceorder[$group] as $choicekey => $choiceid) {
            if ($this->rightchoices[$place] == $choiceid) {
                return $choicekey;
            }
        }
        return null;
    }
    #[\Override]
    public function grade_response(array $response) {
        list($right, $total) = $this->get_num_parts_right($response);
        $fraction = $right / $total;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    #[\Override]
    public function compute_final_grade($responses, $totaltries) {
        $maxitemsdragged = 0;
        $wrongtries = [];
        foreach ($responses as $i => $response) {
            $maxitemsdragged = max($maxitemsdragged,
                                                $this->total_number_of_items_dragged($response));
            $hits = $this->choose_hits($response);
            foreach ($hits as $place => $choiceitem) {
                if (!isset($wrongtries[$place])) {
                    $wrongtries[$place] = $i;
                }
            }
            foreach ($wrongtries as $place => $notused) {
                if (!isset($hits[$place])) {
                    unset($wrongtries[$place]);
                }
            }
        }
        $numtries = count($responses);
        $numright = count($wrongtries);
        $penalty = array_sum($wrongtries) * $this->penalty;
        $grade = ($numright - $penalty) / (max($maxitemsdragged, count($this->places)));
        return $grade;
    }

    #[\Override]
    public function classify_response(array $response) {
        $parts = [];
        $hits = $this->choose_hits($response);
        foreach ($this->places as $placeno => $place) {
            if (isset($hits[$placeno])) {
                $shuffledchoiceno = $this->get_right_choice_for($placeno);
                $choice = $this->get_selected_choice(1, $shuffledchoiceno);
                $parts[$placeno] = new question_classified_response(
                                                    $choice->no,
                                                    $choice->summarise(),
                                                    1 / count($this->places));
            } else {
                $parts[$placeno] = question_classified_response::no_response();
            }
        }
        return $parts;
    }

    #[\Override]
    public function get_correct_response() {
        $responsecoords = [];
        foreach ($this->places as $placeno => $place) {
            $rightchoice = $this->get_right_choice_for($placeno);
            if ($rightchoice !== null) {
                $rightchoicekey = $this->choice($rightchoice);
                $correctcoords = $place->correct_coords();
                if ($correctcoords !== null) {
                    if (!isset($responsecoords[$rightchoicekey])) {
                        $responsecoords[$rightchoicekey] = [];
                    }
                    $responsecoords[$rightchoicekey][] = join(',', $correctcoords);
                }
            }
        }
        $response = [];
        foreach ($responsecoords as $choicekey => $coords) {
            $response[$choicekey] = join(';', $coords);
        }
        return $response;
    }

    #[\Override]
    public function summarise_response(array $response): null|string {
        $hits = $this->choose_hits($response);
        $goodhits = [];
        foreach ($this->places as $placeno => $place) {
            if (isset($hits[$placeno])) {
                $shuffledchoiceno = $this->get_right_choice_for($placeno);
                $choice = $this->get_selected_choice(1, $shuffledchoiceno);
                $goodhits[] = "{".$place->summarise()." -> ". $choice->summarise(). "}";
            }
        }
        if (count($goodhits) == 0) {
            return null;
        }
        return implode(', ', $goodhits);
    }

    #[\Override]
    public function get_random_guess_score() {
        return null;
    }
}

// TODO: what are we dragging?, are we droping a symbole to zonestart and draw the line and finish in zoneend?


