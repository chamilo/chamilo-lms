Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an exercise
    Given I am on "/main/exercise/exercise_admin.php?cidReq=TEMP"
    And I follow "Advanced settings"
    When I fill in the following:
      | exercise_title | Exercise 1 |
    And I fill in ckeditor field "exerciseDescription" with "Exercise description"
    And I press "submitExercise"
    Then I should see "Exercise added"

  Scenario: Add question to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Multiple choice"
    When I fill in the following:
      | questionName | Question Multiple choice |
      | weighting[1] | 10 |
    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer false"
    Then I fill in ckeditor field "answer[3]" with "Answer false"
    Then I fill in ckeditor field "answer[4]" with "Answer false"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment false"
    Then I fill in ckeditor field "comment[3]" with "Comment false"
    Then I fill in ckeditor field "comment[4]" with "Comment false"
    And I press "submitQuestion"
    Then I should see "Item added"

  Scenario: Try exercise "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Start test"
    Then I should see "Question Multiple choice"
    And I check the "Answer true" radio button
    Then I follow "End test"
    And wait for the page to be loaded
    Then I should see "Score for the test: 100 / 100"

  Scenario: Delete an exercise
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The test has been deleted"