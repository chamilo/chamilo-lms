Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an exercise
    Given I am on "/main/exercise/exercise_admin.php?cidReq=TEMP"
    And I press advanced settings
    When I fill in the following:
      | exercise_title | Exercise 1 |
    And I fill in ckeditor field "exerciseDescription" with "Exercise description"
    And I press "submitExercise"
    Then I should see "Exercise added"

#  Scenario: Edit an exercise
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Edit test name and settings"
#    And I press "submitExercise"
#    Then I should see "Test name and settings have been saved."
#
#  Scenario: Add question "Multiple choice" to exercise created "Exercise 1"
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Multiple choice"
#    When I fill in the following:
#      | questionName | Question Multiple choice |
#      | weighting[1] | 10 |
#    Then I fill in ckeditor field "answer[1]" with "Answer true"
#    Then I fill in ckeditor field "answer[2]" with "Answer false"
#    Then I fill in ckeditor field "answer[3]" with "Answer false"
#    Then I fill in ckeditor field "answer[4]" with "Answer false"
#
#    Then I fill in ckeditor field "comment[1]" with "Comment true"
#    Then I fill in ckeditor field "comment[2]" with "Comment false"
#    Then I fill in ckeditor field "comment[3]" with "Comment false"
#    Then I fill in ckeditor field "comment[4]" with "Comment false"
#    And I press "submitQuestion"
#    Then I should see "Item added"
#
#  Scenario: Add question "Multiple answer" to exercise created "Exercise 1"
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Multiple answer"
#    When I fill in the following:
#      | questionName | Question Multiple |
#      | weighting[1] | 10 |
#    Then I check the "correct[1]" radio button
#    Then I fill in ckeditor field "answer[1]" with "Answer true"
#    Then I fill in ckeditor field "answer[2]" with "Answer false"
#    Then I fill in ckeditor field "answer[3]" with "Answer false"
#    Then I fill in ckeditor field "answer[4]" with "Answer false"
#
#    Then I fill in ckeditor field "comment[1]" with "Comment true"
#    Then I fill in ckeditor field "comment[2]" with "Comment false"
#    Then I fill in ckeditor field "comment[3]" with "Comment false"
#    Then I fill in ckeditor field "comment[4]" with "Comment false"
#    And I press "submitQuestion"
#    Then I should see "Item added"
#
#  Scenario: Add question "Matching" to exercise created "Exercise 1"
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Matching"
#    When I fill in the following:
#      | questionName | Question Matching |
#      | answer[1] | Answer A |
#      | answer[2] | Answer B |
#      | option[1] | Option A |
#      | option[2] | Option B |
#    And I fill in select bootstrap static input "#matches_2" select "2"
#
#    And I press "submitQuestion"
#    Then I should see "Item added"
#
#  Scenario: Try exercise "Exercise 1"
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Start test"
#    Then I should see "Question Multiple choice"
#    And I check the "Answer true" radio button
#    And wait for the page to be loaded
#    Then I follow "Next question"
#    And wait for the page to be loaded
#    And I check the "Answer true" radio button
#    And wait for the page to be loaded
#    Then I follow "Next question"
#    And wait for the page to be loaded
#    Then I select "A" from "choice_id_3_1"
#    Then I select "B" from "choice_id_3_2"
#    Then I follow "End test"
#    Then I should see "Score for the test: 40 / 40"

#  Scenario: Check exercise result
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Results and feedback"
#    Then I should see "Learner score"
#    And wait for the page to be loaded
#    And I follow "Grade activity"
#    Then I should see "Score for the test: 20 / 20"
#
#  Scenario: Duplicate exercise
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Copy this exercise as a new one"
#    And I confirm the popup
#    Then I should see "Exercise copied"
#    And I should see "Exercise 1 - Copy"
#
#  Scenario: Delete an exercise
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Delete"
#    And I confirm the popup
#    Then I should see "The test has been deleted"