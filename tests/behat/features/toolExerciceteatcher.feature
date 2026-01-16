Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am logged as "ywarnier"
    And wait very long for the page to be loaded


  Scenario: Create a question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cid=1"
    And wait very long for the page to be loaded
    When I fill in the following:
      | category_name | Category 1 |
    And I fill in editor field "category_description" with "Category 1 description"
    And I press "SubmitNote"
    And wait very long for the page to be loaded
    Then I should see "Category added"

  Scenario: Create a second question category
    Given I am on "/main/exercise/tests_category.php?action=addcategory&cid=1"
    And wait very long for the page to be loaded
    When I fill in the following:
      | category_name | Category 2 |
    And I fill in editor field "category_description" with "Category 2 description"
    And I press "SubmitNote"
    And wait very long for the page to be loaded
    Then I should see "Category added"

  Scenario: Create an exercise
    Given I am on "/main/exercise/exercise_admin.php?cid=1"
    And wait very long for the page to be loaded
    And I press advanced settings
    When I fill in the following:
      | exercise_title | Exercise 1 |
    And I press "submitExercise"
    And wait very long for the page to be loaded
    Then I should see "0 questions"

  Scenario: Edit an exercise
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I wait for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I click the "i.mdi-cog" element
    And wait very long for the page to be loaded
    And I press "submitExercise"
    And wait very long for the page to be loaded
    Then I should see "0 questions"
    And I should not see an error

  Scenario: Add question "Multiple choice" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Multiple choice"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Multiple choice |
      | weighting[ 1]| 10 |
    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"
    Then I fill in editor field "answer3" with "Answer false"
    Then I fill in editor field "answer4" with "Answer false"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    Then I fill in editor field "comment3" with "Comment false"
    Then I fill in editor field "comment4" with "Comment false"
    And I press "submit-question"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Multiple answer" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Multiple answer"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Multiple answers |
      | weighting[1] | 10 |
    Then I check the "correct[1]" radio button
    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"
    Then I fill in editor field "answer3" with "Answer false"
    Then I fill in editor field "answer4" with "Answer false"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    Then I fill in editor field "comment3" with "Comment false"
    Then I fill in editor field "comment4" with "Comment false"
    And I press "submit-question"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Fill in blanks" to "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Fill blanks or form"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Fill blanks |
    Then I fill in editor field "answer" with "Romeo and [Juliet] [Hätten||Haetten] [möchte||moechte] [wäre||waere] [können||koennen] [Könnten||Koennten] [Ärger] [voilà] [müssen] [l'été] [cherchent à] [Übung]  [Ärger|Möglichkeit]"
    And I press "submitQuestion"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Matching" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Matching"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Matching |
    And I fill in editor field "answer1" with "Answer A"
    And I fill in editor field "answer2" with "Answer B"
    And I fill in editor field "option1" with "Option A"
    And I fill in editor field "option2" with "Option B"
    And I fill in select bootstrap static input "#matches_2" select "2"
    And I press "submitQuestion"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Open" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Open"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Open question |
      | weighting | 10 |
    And I press "submitQuestion"
    And wait for the page to be loaded
    Then I should see "Item added"

  Scenario: Add question "Oral expression" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Oral expression"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Oral expression question |
      | weighting | 10 |
    And I press "submitQuestion"
    Then I should not see an error

  Scenario: Add question "Exact answers combination" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Exact Selection"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Exact answers combination |
    Then I check the "correct[1]" radio button
    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"
    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    And I press "submitQuestion"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Unique answer with unknown" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Unique answer with unknown"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Unique answer with unknown |
      | weighting[1] | 10 |
    Then I check the "correct" radio button
    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"
    Then I fill in editor field "answer3" with "Answer false"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    Then I fill in editor field "comment3" with "Comment false"
    And I press "submitQuestion"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Multiple answer true/false/don't know" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Multiple answer true/false/don't know"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Multiple answer true - false - dont know |
   # radio buttonproblem
    Then I check the "correct[1]" radio button
    Then I check the "correct[2]" radio button
    Then I check the "correct[3]" radio button
    Then I check the "correct[4]" radio button

    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer true"
    Then I fill in editor field "answer3" with "Answer true"
    Then I fill in editor field "answer4" with "Answer true"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment true"
    Then I fill in editor field "comment3" with "Comment true"
    Then I fill in editor field "comment4" with "Comment true"
    And I press "submitQuestion"
    And wait very long for the page to be loaded
    And I should not see an error

  Scenario: Add question "Combination true/false/don't-know" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Combination true/false/don't-know"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Combination true - false - don't-know |

    Then I check the "correct[1]" radio button

    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    And I press "submitQuestion"
    And wait for the page to be loaded
    Then I should see "Item added"

  Scenario: Add question "Global multiple answer" to exercise created "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Global multiple answer"
    And wait very long for the page to be loaded
    When I fill in the following:
      | questionName | Global multiple answer |
      | weighting[1] | 10 |

    Then I check the "correct[1]" radio button

    Then I fill in editor field "answer1" with "Answer true"
    Then I fill in editor field "answer2" with "Answer false"
    Then I fill in editor field "answer3" with "Answer false"
    Then I fill in editor field "answer4" with "Answer false"

    Then I fill in editor field "comment1" with "Comment true"
    Then I fill in editor field "comment2" with "Comment false"
    Then I fill in editor field "comment3" with "Comment false"
    Then I fill in editor field "comment4" with "Comment false"

    And I press "submitQuestion"
    And wait for the page to be loaded
    Then I should see "Item added"

  Scenario: Duplicate exercise
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-disc" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should see "copied"
    And wait very long for the page to be loaded
    And I should see "Exercise 1 - Copy"

  Scenario: Import exercise to test questions categories
    Given I am on "/main/exercise/upload_exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I should see "Import quiz from Excel"
    And I attach the file "/tests/fixtures/exercise.xls" to "upload_user_upload_quiz"
    When I press "Upload"
    And wait for the page to be loaded
    Then I should see "Exercise for Behat test"
#
  Scenario: Import exercise from excel
    Given I am on "/main/exercise/upload_exercise.php?cid=1"
    And wait very long for the page to be loaded
    Then I should see "Import quiz from Excel"
    Then I attach the file "/public/main/exercise/quiz_template.xls" to "upload_user_upload_quiz"
    And I press "Upload"
    And wait for the page to be loaded
    Then I should see "Definition of oligarchy"

  Scenario: Try exercise "Exercise 1"
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I follow "Start test"
    And wait very long for the page to be loaded
    # Question 1
    Then I should see "Multiple choice"
    And I check the "Answer true" radio button
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 2
    And I check the "Answer true" radio button
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 3
    Then I fill in the following:
      | choice_id_3_0 | Juliet |
      | choice_id_3_1 | Hätten |
      | choice_id_3_2 | möchte |
      | choice_id_3_3 | wäre |
      | choice_id_3_4 | können |
      | choice_id_3_5 | Könnten |
      | choice_id_3_6 | Ärger |
      | choice_id_3_7 | voilà |
      | choice_id_3_8 | müssen |
      | choice_id_3_9 | l'été |
      | choice_id_3_10 | cherchent à |
      | choice_id_3_11 | Übung |
    #Then I fill in select bootstrap static by text "#choice_id_3_12" select "Ärger"
    Then I select "Ärger" from "choice_id_3_12"
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 4 - Matching
    Then I select "A" from "choice_id_4_1"
    Then I select "B" from "choice_id_4_2"
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 5 - Open question
    Then wait for the page to be loaded
    Then I fill the only ckeditor in the page with "Hello you"
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 6 - Oral question
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 7 - Exact answers combination
    Then I check "Answer true"
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 8 - Unique answer with unknown
    And I check the "Answer true" radio button
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 9 - Multiple answer true - false - dont know
    #@todo
    Then I press "Next question"
    And wait for the page to be loaded
     # Question 10 - Combination true - false - don't-know
    #@todo
    Then I press "Next question"
    And wait for the page to be loaded
    # Question 11 - Global multiple answer
    Then I check "Answer true"
    Then I press "End test"
    And wait for the page to be loaded
    Then I should see "Hello you"
    Then I should see "Score for the test: 83 / 117"

  Scenario: Check exercise result
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Results and feedback"
    Then I should see "Learner score"
    And wait very long for the page to be loaded
    And I check the "semantics" radio button
    And I press "Next question"
    And wait very long for the page to be loaded
    And I check the "RNASL" radio button
    And I press "Next question"
    And wait very long for the page to be loaded
    And I check the "10" radio button
    And I press "Next question"
    And wait very long for the page to be loaded
    And fill in the following:
      | choice_id_6_0 | words  |
      | choice_id_6_1 | fill   |
      | choice_id_6_2 | blanks |
    And I press "Next question"
    And wait very long for the page to be loaded
    And I select "A" from "choice_id_7_1"
    And I select "B" from "choice_id_7_2"
    And I select "C" from "choice_id_7_3"
    And wait very long for the page to be loaded
    And I press "Next question"
    And wait very long for the page to be loaded
    And I check "1"
    And I press "Next question"
    And wait very long for the page to be loaded
    And I press "End test"
    And wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Score for the test: 190 / 190"
    #Y'a pas exactement ce tableau
    And I should see the table "#category_results":
      | Categoryname2 | 50 / 70        | 71.43%         |
      | Categoryname1 | 60 / 60        | 100%           |
      | none          | 80 / 60        | 133.33%        |
      | Total         | 190 / 190      | 100%           |

  Scenario: Teacher looks at exercise results by categories
    Given I am on "/main/index/user_portal.php"
    And wait very long for the page to be loaded
    And I am on "/sessions"
    And wait very long for the page to be loaded
    Then I should see "Session Exercise"
    And wait very long for the page to be loaded
    And I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Exercise for Behat test"
    And wait very long for the page to be loaded
    And I click the "i.mdi-chart-box" element
    And wait very long for the page to be loaded
    Then I should see "Learner score"
    And wait very long for the page to be loaded
    And I click the "i.mdi-checkbox-marked-circle-plus-outline" element
    And wait very long for the page to be loaded
    Then I should see "Score for the test: 190 / 190"
    And I should see the table "#category_results":
      | Categories    | Absolute score | Relative score |
      | Categoryname2 | 50 / 70        | 71.43%         |
      | Categoryname1 | 60 / 60        | 100%           |
      | none          | 80 / 60        | 133.33%        |
      | Total         | 190 / 190      | 100%           |
  Scenario: Delete an exercise
    Given I am on "/main/exercise/exercise.php?cid=1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    Then I should not see "Exercise 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    Then I should not see "Exercise for Behat test"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    Then I should not see "IQ test"

  Scenario: Delete an exercise category
    Given I am on "/main/exercise/tests_category.php?cid=1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    Then I should not see "Category 1"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    Then I should not see "Category 2"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    Then I should not see "Categoryname2"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    Then I should not see "Categoryname1"

  Scenario: Delete session
    Given I am on "/main/session/session_list.php?keyword=Session+Exercise"
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    And wait for the page to be loaded
    Then I should not see "Session Exercise"

  # Scenario: Delete questions (commented)
#  Given I am on "/main/exercise/exercise.php?cid=1"
#  And wait very long for the page to be loaded
#  And I click the "i.mdi-database" element
