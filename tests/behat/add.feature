@qtype @qtype_drawlines
Feature: Test creating a draw lines question
  As a teacher
  In order to test my students
  I need to be able to create drawlines questions

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
  Scenario: Create a draw lines question with one line
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "Draw lines" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a Draw lines question"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_name            | Drawlines01                            |
      | id_questiontext    | Please draw a line from A to B.        |
      | id_generalfeedback | The line start at x and finished at y. |
      | id_status          | Ready                                  |
      | id_defaultmark     | 1                                      |
      | id_grademethod     | partial                                |
    And I upload "question/type/drawlines/tests/fixtures/mkmap.png" file to "Background image" filemanager

    And I set the following fields to these values:
      | id_type_0                          | linesegment                 |
      | id_labelstart_0                    | start 1                     |
      | id_labelmiddle_0                   | mid 1                       |
      | id_labelend_0                      | end 1                       |
      | id_zonestart_0                     | 15,15;10                    |
      | id_zoneend_0                       | 300,15;10                   |
      | For any correct response           | Correct feedback            |
      | For any partially correct response | Partially correct feedback. |
      | For any incorrect response         | Incorrect feedback.         |
      | Hint 1                             | First hint                  |
      | Hint 2                             | Second hint                 |
    When I click on "id_submitbutton" "button"
    Then I should see "Drawlines01"

  @javascript @_file_upload
  Scenario: Create a draw lines question with 2 lines
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "Draw lines" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a Draw lines question"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_name                            | Drawlines02                            |
      | id_questiontext                    | Please draw a line from A to B.        |
      | id_generalfeedback                 | The line start at x and finished at y. |
      | id_status                          | Ready                                  |
      | id_defaultmark                     | 1                                      |
      | id_grademethod                     | partial                                |
    And I upload "question/type/drawlines/tests/fixtures/mkmap.png" file to "Background image" filemanager

    And I set the following fields to these values:
      | id_type_0                          | linesegment  |
      | id_labelstart_0                    | start 1      |
      | id_labelmiddle_0                   | mid 1        |
      | id_labelend_0                      | end 1        |
      | id_zonestart_0                     | 10,10;10     |
      | id_zoneend_0                       | 200,10;10    |

    And I click on "id_addlines" "button"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_type_1                          | linesegment                 |
      | id_labelstart_1                    | start 2                     |
      | id_labelmiddle_1                   | mid 2                       |
      # Do not set End label for this line.
      | id_zonestart_1                     | 10,50;10                    |
      | id_zoneend_1                       | 200,50;10                   |
      | For any correct response           | Correct feedback            |
      | For any partially correct response | Partially correct feedback. |
      | For any incorrect response         | Incorrect feedback.         |
      | Hint 1                             | First hint                  |
      | Hint 2                             | Second hint                 |

    When I click on "id_submitbutton" "button"
    Then I should see "Drawlines02"

  @javascript @_file_upload
  Scenario: Verify that the coordinates of the lines are correctly set
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    When I press "Create a new question ..."
    And I set the field "Draw lines" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a Draw lines question"
    And I expand all fieldsets
    And I upload "question/type/drawlines/tests/fixtures/mkmap.png" file to "Background image" filemanager
    And I click on "id_addlines" "button"
    And I expand all fieldsets

    And I set the following fields to these values:
      | id_type_0      | lineinfinite |
      | id_zonestart_0 | 10,10;10     |
      | id_zoneend_0   | 200,10;10    |
      | id_type_1      | linesegment  |
      | id_zonestart_1 | 50,90;10     |
      | id_zoneend_1   | 180,20;10    |
    Then "//*[name()='svg']/*[name()='g' and @data-dropzone-no='0']/*[name()='polyline' and @points='0,10 10,10 200,10 544,10']" "xpath_element" should exist
    And "//*[name()='svg']/*[name()='g' and @data-dropzone-no='1']/*[name()='polyline' and @points='50,90 180,20']" "xpath_element" should exist

  @javascript @_file_upload
  Scenario: Selecting a line type, should display a line on the background image with default start and end coordinates
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "Draw lines" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a Draw lines question"
    And I expand all fieldsets
    And I upload "question/type/drawlines/tests/fixtures/mkmap.png" file to "Background image" filemanager
    When I select "linesegment" from the "id_type_0" singleselect
    Then "//*[name()='svg']/*[name()='g' and @data-dropzone-no='0']/*[name()='polyline' and @points='15,15 30,30']" "xpath_element" should exist
