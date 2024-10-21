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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
/**
 * Steps definitions related with the drawlines question type.
 *
 * @package   qtype_drawlines
 * @category  test
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_qtype_drawlines extends behat_base {

    /**
     * Get the xpath for a given drag item.
     *
     * @param string $line the line to drag.
     * @param string $part part of the line being dragged.
     * @param bool $iskeyboard is using keyboard or not.
     * @return string the xpath expression.
     */
    protected function line_xpath($line, $part, $iskeyboard = false) {
        $lineno = (int)$line - 1;
        if ($iskeyboard) {
            if ($part == 'line') {
                return '//*[name()="svg"]/*[name()="g" and contains(@class, "choice' . $this->escape($lineno) . '")]';
            } else {
                return '//*[name()="svg"]/*[name()="g" and contains(@class, "choice' . $this->escape($lineno) . '")]' .
                '/*[name()="circle" and contains(@class, "' . $this->escape($part) . '")]';
            }
        }
    }

    /**
     * Type some characters while focused on a given line.
     *
     * @param string $direction the direction key to press.
     * @param int $
     * @param string $part the part of the line to move.
     * @param string $line the line to drag. The label, optionally followed by ,<instance number> (int) if relevant.
     *
     * @Given /^I type "(?P<direction>up|down|left|right)" "(?P<repeats>\d+)" times on line "(?P<line>\d+)" "(?P<endpoint>line|startcircle|endcircle)" in the drawlines question$/
     */
    public function i_type_on_line_in_the_drawlines_question($direction, $repeats, $line, $part) {
        $node = $this->get_selected_node('xpath_element', $this->line_xpath($line, $part, true));
        $this->ensure_node_is_visible($node);
        $node->focus();
        for ($i = 0; $i < $repeats; $i++) {
            $this->execute('behat_general::i_press_named_key', ['', $direction]);
        }
    }
}
