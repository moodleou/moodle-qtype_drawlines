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
      | questioncategory | qtype     | name                           | template                 |
      | Test questions   | drawlines | Drawlines to preview partial   | mkmap_twolines           |
      | Test questions   | drawlines | Drawlines to preview allornone | mkmap_twolines_allornone |

  @javascript
  Scenario: Preview a question with partial grading using the keyboard
    Given I am on the "Drawlines to preview partial" "core_question > preview" page logged in as teacher
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "40" times on line "1" "line" in the drawlines question
    And I type "down" "190" times on line "1" "endcircle" in the drawlines question
    And I type "left" "200" times on line "1" "endcircle" in the drawlines question
    When I press "Submit and finish"
    Then the state of "Draw 2 lines on the map" question is shown as "Partially correct"
    And I should see "Mark 0.50 out of 1.00"

  @javascript
  Scenario: Preview a question with partial grading using the keyboard with correct answers
    Given I am on the "Drawlines to preview partial" "core_question > preview" page logged in as teacher
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "40" times on line "1" "line" in the drawlines question
    And I type "down" "190" times on line "1" "endcircle" in the drawlines question
    And I type "left" "200" times on line "1" "endcircle" in the drawlines question
    And I type "up" "360" times on line "2" "line" in the drawlines question
    And I type "right" "240" times on line "2" "line" in the drawlines question
    And I type "down" "190" times on line "2" "endcircle" in the drawlines question
    And I type "left" "150" times on line "2" "endcircle" in the drawlines question
    When I press "Submit and finish"
    Then the state of "Draw 2 lines on the map" question is shown as "Correct"
    And I should see "Mark 1.00 out of 1.00"
    And I should see "Well done!"
    And I should see "We draw lines from a starting to an end point."

  @javascript
  Scenario: Preview a question with partial grading using the keyboard with incorrect answers
    Given I am on the "Drawlines to preview partial" "core_question > preview" page logged in as teacher
    And I type "up" "60" times on line "1" "line" in the drawlines question
    And I type "up" "60" times on line "2" "line" in the drawlines question
    And I type "right" "200" times on line "2" "line" in the drawlines question
    When I press "Submit and finish"
    Then the state of "Draw 2 lines on the map" question is shown as "Incorrect"
    And I should see "Mark 0.00 out of 1.00"
    And I should see "That is not right at all."
    And I should see "We draw lines from a starting to an end point."

  @javascript
  Scenario: Preview a question using interactive with multiple tries with partical garding
    Given I am on the "Drawlines to preview partial" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Interactive with multiple tries"
    And I press "id_saverestart"
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "40" times on line "1" "line" in the drawlines question
    And I type "up" "360" times on line "2" "line" in the drawlines question
    And I type "right" "240" times on line "2" "line" in the drawlines question
    When I press "Check"
    Then I should see "Tries remaining: 2"
    And I should see "Marked out of 1.00"
    And I should see "Parts, but only parts, of your response are correct."
    And I should see "You have correctly selected two coordinates."
    And I press "Try again"
    And I type "up" "360" times on line "2" "line" in the drawlines question
    And I type "right" "0" times on line "2" "line" in the drawlines question
    And I type "down" "190" times on line "2" "endcircle" in the drawlines question
    And I type "left" "150" times on line "2" "endcircle" in the drawlines question
    And I press "Check"
    And I should see "Tries remaining: 1"
    And I should see "Marked out of 1.00"
    And I should see "Parts, but only parts, of your response are correct."
    And I should see "You have correctly selected three coordinates."
    And I should see "The coordinate " in the "div.misplacedinfo" "css_element"
    And I should see "Line 1 end(160,10)" in the "span.misplaced" "css_element"
    And I should see " is placed incorrectly." in the "div.misplacedinfo" "css_element"
    And I press "Try again"
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "0" times on line "1" "line" in the drawlines question
    And I type "down" "190" times on line "1" "endcircle" in the drawlines question
    And I type "left" "200" times on line "1" "endcircle" in the drawlines question
    And I press "Check"
    And the state of "Draw 2 lines on the map" question is shown as "Correct"
    And I should see "Mark 0.33 out of 1.00"
    And I should see "Well done!"
    And I should see "We draw lines from a starting to an end point."

  @javascript
  Scenario: Preview a question using interactive with multiple tries with all-or-none grading
    Given I am on the "Drawlines to preview allornone" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the field "How questions behave" to "Interactive with multiple tries"
    And I press "id_saverestart"
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "40" times on line "1" "line" in the drawlines question
    And I type "up" "360" times on line "2" "line" in the drawlines question
    And I type "right" "240" times on line "2" "line" in the drawlines question
    When I press "Check"
    Then I should see "Tries remaining: 2"
    And I should see "Marked out of 1.00"
    And I should see "That is not right at all."
    And I press "Try again"
    And I type "up" "360" times on line "2" "line" in the drawlines question
    And I type "right" "0" times on line "2" "line" in the drawlines question
    And I type "down" "190" times on line "2" "endcircle" in the drawlines question
    And I type "left" "150" times on line "2" "endcircle" in the drawlines question
    And I press "Check"
    And I should see "Tries remaining: 1"
    And I should see "Marked out of 1.00"
    And I should see "Parts, but only parts, of your response are correct."
    And I should see "You have correctly selected one line."
    And I should see "You have to find the positions for start and end of each line as described in the question text."
    And I should see "The " in the "div.misplacedinfo" "css_element"
    And I should see "Line 1 start(10,10) end(160,10)" in the "span.misplaced" "css_element"
    And I should see " is placed incorrectly." in the "div.misplacedinfo" "css_element"
    And I press "Try again"
    And I type "up" "360" times on line "1" "line" in the drawlines question
    And I type "left" "0" times on line "1" "line" in the drawlines question
    And I type "down" "190" times on line "1" "endcircle" in the drawlines question
    And I type "left" "200" times on line "1" "endcircle" in the drawlines question
    And I press "Check"
    And the state of "Draw 2 lines on the map" question is shown as "Correct"
    And I should see "Mark 0.33 out of 1.00"
    And I should see "Well done!"
    And I should see "We draw lines from a starting to an end point."
