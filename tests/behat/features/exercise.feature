Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an exercise
    Given I am on "/main/exercise/exercise_admin.php?cidReq=TEMP"
    When I fill in the following:
      | exercise_title | Exercise 1 |
      | exerciseDescription    | Exercise description |
    And I press "submitExercise"
    Then I should see "Exercise added"

#    Scenario: Create an exercise with a question
#    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
#    And I follow "Exercise 1"
#    And I follow "Edit"
#    And I follow "Multiple choice"
#    When I fill in the following:
#      | questionName | Question Multiple choice |
##      | answer[1]    | Answer 1 |
##      | answer[2]    | Answer 2 |
##      | answer[3]    | Answer 3 |
##      | answer[4]    | Answer 4 |
##      | weighting[1] | 10 |
##    Then I fill in wysiwyg field "answer[1]" with "Answer 1"
#    And I press "submitQuestion"
#    Then I should see "Item added"
#
#


