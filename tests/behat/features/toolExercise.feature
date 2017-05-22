Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cidReq=TEMP"
    And wait for the page to be loaded
    When I fill in the following:
      | category_name | Category 1 |
    And I fill in ckeditor field "category_description" with "Category 1 description"

    And I press "SubmitNote"
    Then I should see "Category added"

  Scenario: Create a second question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cidReq=TEMP"
    And wait for the page to be loaded
    When I fill in the following:
      | category_name | Category 2 |
    And I fill in ckeditor field "category_description" with "Category 2 description"
    And I press "SubmitNote"
    Then I should see "Category added"

  Scenario: Create an exercise
    Given I am on "/main/exercise/exercise_admin.php?cidReq=TEMP"
    And I press advanced settings
    When I fill in the following:
      | exercise_title | Exercise 1 |
    And I fill in ckeditor field "exerciseDescription" with "Exercise description"
    And I press "submitExercise"
    Then I should see "Exercise added"

  Scenario: Edit an exercise
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Edit test name and settings"
    And I press "submitExercise"
    Then I should see "Test name and settings have been saved."

  Scenario: Add question "Multiple choice" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Multiple choice"
    When I fill in the following:
      | questionName | Multiple choice |
      | weighting[1] | 10 |
    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer false"
    Then I fill in ckeditor field "answer[3]" with "Answer false"
    Then I fill in ckeditor field "answer[4]" with "Answer false"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment false"
    Then I fill in ckeditor field "comment[3]" with "Comment false"
    Then I fill in ckeditor field "comment[4]" with "Comment false"
    And I press "submit-question"
    Then I should see "Item added"

  Scenario: Add question "Multiple answer" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Multiple answer"
    When I fill in the following:
      | questionName | Multiple answers |
      | weighting[1] | 10 |
    Then I check the "correct[1]" radio button
    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer false"
    Then I fill in ckeditor field "answer[3]" with "Answer false"
    Then I fill in ckeditor field "answer[4]" with "Answer false"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment false"
    Then I fill in ckeditor field "comment[3]" with "Comment false"
    Then I fill in ckeditor field "comment[4]" with "Comment false"
    And I press "submit-question"
    Then I should see "Item added"

  Scenario: Add question "Fill in blanks" to "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Fill blanks or form"
    When I fill in the following:
      | questionName | Fill blanks |
    Then I fill in ckeditor field "answer" with "Romeo and [Juliet]"
    And I press "submitQuestion"
    Then I should see "Item added"

  Scenario: Add question "Matching" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Matching"
    When I fill in the following:
      | questionName | Matching |
    And I fill in ckeditor field "answer[1]" with "Answer A"
    And I fill in ckeditor field "answer[2]" with "Answer B"
    And I fill in ckeditor field "option[1]" with "Option A"
    And I fill in ckeditor field "option[2]" with "Option B"
    And I fill in select bootstrap static input "#matches_2" select "2"
    And I press "submitQuestion"
    Then I should see "Item added"

    Scenario: Add question "Open" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Open"
    When I fill in the following:
      | questionName | Open question |
      | weighting | 10 |
    And I press "submitQuestion"
    Then I should see "Item added"

    Scenario: Add question "Oral expression" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Oral expression"
    When I fill in the following:
      | questionName | Oral expression question |
      | weighting | 10 |
    And I press "submitQuestion"
    Then I should see "Item added"

  Scenario: Add question "Exact answers combination" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Exact Selection"
    When I fill in the following:
      | questionName | Exact answers combination |
    Then I check the "correct[1]" radio button
    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer false"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment false"
    And I press "submitQuestion"
    Then I should see "Item added"

    Scenario: Add question "Unique answer with unknown" to exercise created "Exercise 1"
      Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
      And I follow "Exercise 1"
      And I follow "Edit"
      And I follow "Unique answer with unknown"
      When I fill in the following:
        | questionName | Unique answer with unknown |
        | weighting[1] | 10 |
      Then I check the "correct" radio button
      Then I fill in ckeditor field "answer[1]" with "Answer true"
      Then I fill in ckeditor field "answer[2]" with "Answer false"
      Then I fill in ckeditor field "answer[3]" with "Answer false"

      Then I fill in ckeditor field "comment[1]" with "Comment true"
      Then I fill in ckeditor field "comment[2]" with "Comment false"
      Then I fill in ckeditor field "comment[3]" with "Comment false"
      And I press "submitQuestion"
      Then I should see "Item added"

  Scenario: Add question "Multiple answer true/false/don't know" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Multiple answer true/false/don't know"
    When I fill in the following:
      | questionName | Multiple answer true - false - dont know |

    Then I check the "correct[1]" radio button
    Then I check the "correct[2]" radio button
    Then I check the "correct[3]" radio button
    Then I check the "correct[4]" radio button

    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer true"
    Then I fill in ckeditor field "answer[3]" with "Answer true"
    Then I fill in ckeditor field "answer[4]" with "Answer true"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment true"
    Then I fill in ckeditor field "comment[3]" with "Comment true"
    Then I fill in ckeditor field "comment[4]" with "Comment true"
    And I press "submitQuestion"
    Then I should see "Item added"

  Scenario: Add question "Combination true/false/don't-know" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Combination true/false/don't-know"
    When I fill in the following:
      | questionName | Combination true - false - don't-know |

    Then I check the "correct[1]" radio button

    Then I fill in ckeditor field "answer[1]" with "Answer true"
    Then I fill in ckeditor field "answer[2]" with "Answer false"

    Then I fill in ckeditor field "comment[1]" with "Comment true"
    Then I fill in ckeditor field "comment[2]" with "Comment false"
    And I press "submitQuestion"
    Then I should see "Item added"


  Scenario: Add question "Global multiple answer" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Global multiple answer"
    When I fill in the following:
      | questionName | Global multiple answer |
      | weighting[1] | 10 |

    Then I check the "correct[1]" radio button

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
    # Question 1
    Then I should see "Multiple choice"
    And I check the "Answer true" radio button
    And wait for the page to be loaded
    Then I press "Next question"
    # Question 2
    And wait for the page to be loaded
    And I check the "Answer true" radio button
    And wait for the page to be loaded
    Then I press "Next question"
    # Question 3
    Then I fill in the following:
      | choice_id_3_0 | Juliet |
    And wait for the page to be loaded
    Then I press "Next question"
    # Question 4 - Matching
    Then I select "A" from "choice_id_4_1"
    Then I select "B" from "choice_id_4_2"
    Then I press "Next question"
    # Question 5 - Open question
    #Then I fill in ckeditor field "<string>" with "<string>"
    Then wait for the page to be loaded
    Then I press "Next question"
    # Question 6 - Oral question
    Then wait for the page to be loaded
    Then I press "Next question"
    # Question 7 - Exact answers combination
    #Then I check radio button with label "Answer true"
    Then I press "Next question"
    # Question 8 - Unique answer with unknown
    #@todo
    Then I press "Next question"
    # Question 9 - Multiple answer true - false - dont know
    #@todo
    Then I press "Next question"
     # Question 10 - Combination true - false - don't-know
    #@todo
    Then I press "Next question"
    # Question 11 - Global multiple answer
    #Then I check radio button with label "Answer true"
    Then I press "End test"
    Then I should see "Score for the test: 41 / 105"

  Scenario: Check exercise result
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Exercise 1"
    And I follow "Edit"
    And I follow "Results and feedback"
    Then I should see "Learner score"
    And wait for the page to be loaded
    And I follow "Grade activity"
    Then I should see "Score for the test: 41 / 105"

  Scenario: Duplicate exercise
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Copy this exercise as a new one"
    And I confirm the popup
    Then I should see "Exercise copied"
    And I should see "Exercise 1 - Copy"

  Scenario: Delete an exercise
    Given I am on "/main/exercise/exercise.php?cidReq=TEMP"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The test has been deleted"

  Scenario: Delete an exercise category
    Given I am on "/main/exercise/tests_category.php?cidReq=TEMP"
    And I follow "Delete"
    Then I should see "Category deleted"

  Scenario: Delete an exercise category
    Given I am on "/main/exercise/tests_category.php?cidReq=TEMP"
    And I follow "Delete"
    Then I should see "Category deleted"