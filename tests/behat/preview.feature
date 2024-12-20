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

  @javascript
  Scenario: Preview a question using the keyboard
    Given I am on the "Drawlines to preview" "core_question > preview" page logged in as teacher
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "40" times on line "1" "line" in the drawlines question
    And I type "down" "190" times on line "1" "endcircle" in the drawlines question
    And I type "left" "200" times on line "1" "endcircle" in the drawlines question
    When I press "Submit and finish"
    Then the state of "Draw 2 lines on the map" question is shown as "Partially correct"
    And I should see "Mark 0.50 out of 1.00"
