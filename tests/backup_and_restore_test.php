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

use question_bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/externallib.php');
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Tests for drawlines question type backup and restore.
 *
 * @package   qtype_drawlines
 * @copyright 2024 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class backup_and_restore_test extends \restore_date_testcase {

    /**
     * Backup and restore the course containing an drawlines question for testing drawlines backup and restore.
     * @covers \restore_qtype_drawlines_plugin
     */
    public function test_restore_create_qtype_drawlines_mkmap_twolines(): void {
        global $DB;

        // Create a course with one drawlines question in its question bank.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $contexts = new \core_question\local\bank\question_edit_contexts(\context_course::instance($course->id));
        $category = question_make_default_categories($contexts->all());
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $drawlines = $questiongenerator->create_question('drawlines', 'mkmap_twolines', ['category' => $category->id]);

        // Do backup and restore the course.
        $newcourseid = $this->backup_and_restore($course);

        // Verify that the restored question has the extra data such as options, lines.
        $contexts = new \core_question\local\bank\question_edit_contexts(\context_course::instance($newcourseid));
        $newcategory = question_make_default_categories($contexts->all());
        $newdrawlines = $DB->get_record_sql('SELECT q.*
                                              FROM {question} q
                                              JOIN {question_versions} qv ON qv.questionid = q.id
                                              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                                             WHERE qbe.questioncategoryid = ?
                                               AND q.qtype = ?', [$newcategory->id, 'drawlines']);

        $newdrawlines->options = $DB->get_record('qtype_drawlines_options', ['questionid' => $newdrawlines->id]);
        $newdrawlines->lines = $DB->get_records('qtype_drawlines_lines', ['questionid' => $newdrawlines->id]);

        $this->assertSame($newcourseid, $course->id + 1);
        $this->assertSame((int)$newdrawlines->id, $drawlines->id + 1);

        $this->assertTrue($DB->record_exists('question', ['id' => $newdrawlines->id]));
        $this->assertTrue($DB->record_exists('qtype_drawlines_options', ['questionid' => $newdrawlines->id]));
        $this->assertTrue($DB->record_exists('qtype_drawlines_lines', ['questionid' => $newdrawlines->id]));

        $questionata = question_bank::load_question_data($newdrawlines->id);

        $this->assertSame('partial', $questionata->options->grademethod);

        $questionlines = array_values((array) $questionata->lines);
        $this->assertSame($newdrawlines->id, $questionlines[0]->questionid);
        $this->assertSame('1', $questionlines[0]->number);
        $this->assertSame(line::TYPE_LINE_SEGMENT, $questionlines[0]->type);
        $this->assertSame('Start 1', $questionlines[0]->labelstart);
        $this->assertSame('Mid 1', $questionlines[0]->labelmiddle);
        $this->assertSame('', $questionlines[0]->labelend);
        $this->assertSame('10,10;12', $questionlines[0]->zonestart);
        $this->assertSame('10,200;12', $questionlines[0]->zoneend);

        $this->assertSame($newdrawlines->id, $questionlines[1]->questionid);
        $this->assertSame('2', $questionlines[1]->number);
        $this->assertSame(line::TYPE_LINE_SEGMENT, $questionlines[1]->type);
        $this->assertSame('Start 2', $questionlines[1]->labelstart);
        $this->assertSame('Mid 2', $questionlines[1]->labelmiddle);
        $this->assertSame('End 2', $questionlines[1]->labelend);
        $this->assertSame('300,10;12', $questionlines[1]->zonestart);
        $this->assertSame('300,200;12', $questionlines[1]->zoneend);
    }
}
