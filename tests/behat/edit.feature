@qtype @qtype_drawlines
Feature: Test editing an DrawLines question
  As a teacher
  In order to be able to update my DrawLines question
  I need to edit them

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
      | questioncategory | qtype     | name              | template       |
      | Test questions   | drawlines | Drawlines to edit | mkmap_twolines |

  @javascript
  Scenario: Edit and validate a DrawLines question
    Given I am on the "Drawlines to edit" "core_question > edit" page logged in as teacher
    And I should see "Editing a DrawLines question"
    And I should see "Line 1"
    And I should see "Line 2"
    And I click on "Line 1" "link"
    And I should see "Line segment ---"
    When I set the field "id_zonestart_0" to "10,10,12"
    And I press "id_submitbutton"
    Then I should see "Start zone coordinates should be in x,y;r format, where x,y are the coordinates of the centre of a circle and r is the radius."
    # Correct the input for end zone coordinates.
    And I set the field "id_zonestart_0" to "10,10;12"

    And I click on "Line 2" "button"
    And I set the field "id_type_1" to "Choose"
    And I press "id_submitbutton"
    And I should see "You have to select a type for Line 2"
    And I set the field "id_type_1" to "Single arrow -→"

    # Chnage the question name
    And I set the following fields to these values:
      | Question name | Drawline edited |
    And I press "id_submitbutton"
    Then I should see "Drawline edited"
