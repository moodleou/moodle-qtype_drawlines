<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     qtype_drawlines
 * @copyright   2024 The Open University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $grademethod = [
            'partial' => new lang_string('gradepartialcredit', 'qtype_drawlines'),
            'allnone' => new lang_string('gradeallornothing', 'qtype_drawlines'),
    ];
    $settings->add(new admin_setting_configselect('qtype_drawlines/grademethod',
            new lang_string('grademethod', 'qtype_drawlines'),
            new lang_string('grademethod_desc', 'qtype_drawlines'), 'partial', $grademethod));
}
