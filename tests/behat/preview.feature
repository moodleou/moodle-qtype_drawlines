@qtype @qtype_drawlines @_switch_window
Feature: Preview a DrawLines question
  As a teacher
  In order to check my DrawLines questions will work for students
  I need to preview them

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
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name                 | template       |
      | Test questions   | drawlines | Drawlines to preview | mkmap_twolines |
  @javascript @_bug_phantomjs
  Scenario: Preview a question using the mouse
    When I am on the "Drawlines to preview" "core_question > preview" page logged in as teacher
    And I pause
    # TODO: Finishing this scenario after Js completed and adding other  possible scenarios.
