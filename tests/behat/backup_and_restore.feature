@qtype @qtype_drawlines
Feature: Test duplicating a course containing a DrawLines question
  As a teacher
  In order re-use my courses containing DrawLines questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name          | template       |
      | Test questions   | drawlines | Draw lines 01 | mkmap_twolines |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | Draw lines 01 | 1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Backup and restore a course containing a DrawLines question
    Given I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    When I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    And I choose "Edit question" action for "Draw lines 01" in the question bank
    Then the following fields match these values:
      | id_name            | Draw lines 01                                                                                                                                                                 |
      | id_questiontext    | <P>Draw 2 lines on the map. A line segment from A (line starting point) to B (line Ending point), and another one from C to D. A is ..., B is ..., C is ... and D is ...</P> |
      | id_status          | Ready                                                 |
      | id_defaultmark     | 1                                                     |
      | id_generalfeedback | <p>We draw lines from a starting to an end point.</p> |
      | id_grademethod     | partial                                               |
    And I click on "Line 1" "link"
    And the following fields match these values:
      | id_type_0                          | linesegment  |
      | id_labelstart_0                    | Start 1      |
      | id_labelmiddle_0                   | Mid 1        |
      | id_labelend_0                      |              |
      | id_zonestart_0                     | 10,10;12     |
      | id_zoneend_0                       | 300,10;12    |
    And I click on "Line 2" "link"
    And the following fields match these values:
      | id_type_1                          | linesegment  |
      | id_labelstart_1                    | Start 2      |
      | id_labelmiddle_1                   | Mid 2        |
      | id_labelend_1                      | End 2        |
      | id_zonestart_1                     | 10,100;12    |
      | id_zoneend_1                       | 300,100;12   |
    And I click on "Combined feedback" "link"
    And the following fields match these values:
      | For any correct response           | Well done!                                           |
      | For any partially correct response | Parts, but only parts, of your response are correct. |
      | For any incorrect response         | That is not right at all.                            |
    And I click on "Multiple tries" "link"
    And the following fields match these values:
      | Hint 1  | You are trying to draw 2 lines by placing the start and end markers for each line on the map.   |
      | Hint 2  | You have to find the positins for start and end of each line as described in the question text. |
