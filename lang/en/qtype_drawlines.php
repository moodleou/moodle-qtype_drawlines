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

/**
 * Language strings for qtype_drawlines.
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addmoreblanks'] = 'Blanks for {no} more {$a}';
$string['alttext'] = 'Alt text';
$string['answer'] = 'Answer';

$string['bgimage'] = 'Background image';

$string['correctansweris'] = 'The correct answer is: {$a}';

$string['dropbackground'] = 'Background image for DrawLines';

$string['correctansweris'] = 'The correct answer is: {$a}';
$string['correctanswersare'] = 'The correct answers are: {$a}';

$string['formerror_nobgimage'] = 'You need to select an image to use as the background for the drag and drop area.';
$string['formerror_nolines'] = 'You need to expand \'Line 1\' and fill the form for it';
$string['formerror_notype'] = 'You have to select a type for Line {$a}';
$string['formerror_zonestart'] = 'Start zone coordinates should be in x,y;r format, where x,y are the coordinates of the centre of a circle and r is the radius.';
$string['formerror_zoneend'] = 'End zone coordinates should be in x,y;r format, where x,y are the coordinates of the centre of a circle and r is the radius.';

$string['grademethod'] = 'Grading type';
$string['grademethod_desc'] = 'Give partial credit (default): each correct response in the body cells are worth one point, so students score a percentage of the total correct responses.
All or nothing: students must get every response correct, otherwise they score zero.';
$string['grademethod_help'] = 'Give partial credit (default): each correct response worth one point, so students score a percentage of the total correct responses.

All or nothing: students must get every response correct, otherwise they score zero.';
$string['gradepartialcredit'] = 'Give partial credit';
$string['gradeallornothing'] = 'All-or-nothing';

$string['labelstart'] = 'Start label';
$string['labelmiddle'] = 'Mid label';
$string['labelend'] = 'End label';
$string['linesegment'] = 'Line segment ---';
$string['linesinglearrow'] = 'Single arrow -→';
$string['linedoublearrows'] = 'Double arrows ←--→';
$string['lineinfinite'] = 'Infinite line --o--o--';
$string['linexheader'] = 'Line {no}';

$string['pleasedragalllines'] = 'Your answer is not complete; you must place all lines on the image.';
$string['pluginname'] = 'DrawLines';
$string['pluginname_help'] = 'DrawLines require the respondent to position the lines on a background image.';
$string['pluginname_link'] = 'question/type/drawlines';
$string['pluginnameadding'] = 'Adding a DrawLines question';
$string['pluginnameediting'] = 'Editing a DrawLines question';
$string['pluginnamesummary'] = 'Two markers that control a line are dragged and dropped onto a background image.

Note: This question type is not accessible to users who are visually impaired.';
$string['previewareaheader'] = 'Preview';
$string['previewareamessage'] = 'Select a background image file, enter text labels for markers and define the drop zones on the background image to which they must be dragged.';
$string['privacy:preference:defaultmark'] = 'The default mark set for a given question.';
$string['privacy:preference:grademethod'] = 'The penalty for each incorrect try when questions are run using the \'Interactive with multiple tries\' or \'Adaptive mode\' behaviour.';
$string['privacy:preference:penalty'] = 'The penalty for each incorrect try when questions are run using the \'Interactive with multiple tries\' or \'Adaptive mode\' behaviour.';

$string['refresh'] = 'Refresh preview';

$string['showmisplaced'] = 'State which zones are incorrectly placed';
$string['summarisechoice'] = '{$a->no}. {$a->text}';
$string['summariseplace'] = '{$a->no}. {$a->text}';
$string['summarisechoiceno'] = 'Item {$a}';
$string['summariseplaceno'] = 'Drop zone {$a}';

$string['type'] = 'Type';
$string['type_help'] = 'You can choose whether the line doesn’t have a beginning or end (line), has one or more ends (right, left, and double arrows), or it only matters that the line intersects specific points on the graph (intersect points).';

$string['xleft'] = 'Left';

$string['yougot1right'] = 'You have correctly selected one point.';
$string['yougotnright'] = 'You have correctly selected {$a->num} points.';
$string['yougot1rightline'] = 'You have correctly selected one line.';
$string['yougotnrightline'] = 'You have correctly selected {$a->num} lines.';
$string['ytop'] = 'Top';

$string['zonecoordinates'] = 'x,y;r (where x,y are the coordinates of the centre of the circle and r is the radius)';
$string['zonestart'] = 'Start zone coordinates';
$string['zoneend'] = 'End zone coordinates';
