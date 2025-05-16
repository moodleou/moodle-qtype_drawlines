@qtype @qtype_drawlines
Feature: Test importing draw lines questions
  As a teacher
  In order to reuse draw lines questions
  I need to import them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |

  @javascript @_file_upload
  Scenario: Import a draw lines question with 2 lines.
    Given I am on the "Course 1" "core_question > course question import" page logged in as teacher
    When I set the field "id_format_xml" to "1"
    And I upload "question/type/drawlines/tests/fixtures/testquestion_drawlines_mkmap_twolines.xml" file to "Import" filemanager
    And I expand all fieldsets
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 1 questions from file"
    And I should see "1. Draw 2 lines on the map. A line segment from A (line starting point) to B (line Ending point), and another one from C to D."
    And I press "Continue"
    And I should see "Drawlines to edit"

  @javascript @_file_upload
  Scenario: Verify the imported question for hints.
    Given I am on the "Course 1" "core_question > course question import" page logged in as teacher
    And I set the field "id_format_xml" to "1"
    And I upload "question/type/drawlines/tests/fixtures/testquestion_drawlines_mkmap_twolines.xml" file to "Import" filemanager
    And I expand all fieldsets
    And I press "id_submitbutton"
    And I should see "Parsing questions from import file."
    And I press "Continue"
    And I should see "Drawlines to edit"
    When I am on the "Drawlines to edit" "core_question > edit" page logged in as "teacher"
    And I expand all fieldsets
    Then the following fields match these values:
      | hintshownumcorrect[0] | Checked |
      | hintshownumcorrect[1] | Checked |
      | hintshowmisplaced[0]  |         |
      | hintshowmisplaced[1]  | Checked |
