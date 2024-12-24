@qtype @qtype_drawlines
Feature: Test editing an draw lines question
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
    And I should see "Editing a Draw lines question"
    And I should see "Line 1"
    And I should see "Line 2"
    And I click on "Line 1" "link"
    And I should see "Line segment ---"
    When I set the field "id_zonestart_0" to "10,10,12"
    And I press "id_submitbutton"
    Then I should see "Start zone coordinates should be in x,y;r format, where x,y are the coordinates of the centre of a circle and r is the radius."
    # Correct the input for end zone coordinates.
    And I set the field "id_zonestart_0" to "10,10;12"

    # Verify that the zone coordinates are reset when line type is set to 'Choose'.
    And I click on "Line 2" "link"
    And I set the field "id_type_1" to "Choose"
    And I should see "" in the "#id_zonestart_1" "css_element"
    And I should see "" in the "#id_zoneend_1" "css_element"

    # Validate that the line type is set, when there are input zone coordinates.
    And I click on "id_addlines" "button"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_zonestart_2 | 10,50;10  |
      | id_zoneend_2   | 200,50;10 |
    And I press "id_submitbutton"
    And I should see "You have to select a type for Line 3"
    And I set the field "id_type_2" to "Single arrow ⟶"

    # Change the question name
    And I set the following fields to these values:
      | Question name | Drawline edited |
    And I press "id_submitbutton"
    Then I should see "Drawline edited"

  @javascript
  Scenario: Editing DrawLines question labels should rellect in svg
    Given I am on the "Drawlines to edit" "core_question > edit" page logged in as teacher
    And I should see "Editing a Draw lines question"
    And I expand all fieldsets
    When I set the field "id_labelstart_0" to "new label start"
    And I set the field "id_labelmiddle_0" to "new label middle"
    And I set the field "id_labelend_0" to "new label end"
    Then "//*[name()='svg']/*[name()='g' and @data-dropzone-no='0']/*[name()='text'][1][contains(text(), 'new label start')]" "xpath_element" should exist
    And "//*[name()='svg']/*[name()='g' and @data-dropzone-no='0']/*[name()='text'][2][contains(text(), 'new label middle')]" "xpath_element" should exist
    And "//*[name()='svg']/*[name()='g' and @data-dropzone-no='0']/*[name()='text'][3][contains(text(), 'new label end')]" "xpath_element" should exist

  @javascript @_file_upload
  Scenario: Validate the background image size for Draw lines question
    Given I am on the "Drawlines to edit" "core_question > edit" page logged in as teacher
    And I should see "Editing a Draw lines question"
    When I upload "question/type/drawlines/tests/fixtures/grid_650x600.png" file to "Background image" filemanager
    And I press "id_submitbutton"
    Then I should see "Image file should not be larger than 600x600 px. The uploaded image file size is 650x600 px."
