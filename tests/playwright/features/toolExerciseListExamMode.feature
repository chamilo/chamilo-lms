# The Status column of the learner's exercise list showed "Latest attempt : 100 % (1 / 1)" even
# for a test whose results mode hides the score ("Exam mode: Do not show score nor answers"):
# the exercise page and the results page kept the score hidden, but the list gave it away, and
# the exercise list API sent it to the browser. The list now follows the same rule as the
# exercise page (ExerciseScoreVisibilityHelper): when the score is hidden, it shows "Already
# attempted" and the API no longer sends the score. "Not attempted" was also shown untranslated.
#
# Verified live on the Vue exercise tool (ExerciseListView.vue):
# - the "Show score to learner" options of the configuration form are radio buttons named
#   "resultsDisabled" (value 1 = exam mode), shown once "Advanced settings" is open;
# - each exercise of the list is a table row, so the status is checked within the row of its
#   exercise, not page-wide (the three tests are on the same page).
#
# Uses its own course EXAMLIST, deleted at the end, for the same reason as
# toolExerciseTeacher.feature: the shared TEMP course is filled and emptied by other files
# running in parallel.
@common @tools
Feature: Score of the latest attempt in the exercise list
  In order to keep exam results secret
  As a learner
  I want the exercise list to hide the score of a test whose results are hidden

  Scenario: Create the exam mode list course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXAMLIST"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=EXAMLIST"
    And I wait for the page to be loaded
    Then I should see "EXAMLIST"

  Scenario: Subscribe the learner to the exam mode list course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | search | acostea |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Costea"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: Create a test in exam mode
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Exam mode test"
    And I press "Advanced settings"
    And I check the "resultsDisabled" radio button with "1" value
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"

  Scenario: Create a test that shows the score
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Score shown test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"

  Scenario: Create a test the learner will not try
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Untried test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"

  Scenario Outline: Add a multiple choice question to "<test>"
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "<test>"
    And I wait for the page content to settle
    And I follow the question type "Multiple choice"
    And I wait for the page content to settle
    When I fill in "question" with "Question of <test>"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I mark answer 1 as correct
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Question of <test>"
    And I should not see an error

    Examples:
      | test             |
      | Exam mode test   |
      | Score shown test |
      | Untried test     |

  # A new test is visible to learners from the start: "Default visibility of new tests" is "visible"
  # by default (ResourceDefaultVisibilityHelper). Check it instead of activating it.
  Scenario Outline: "<test>" is visible
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    Then I wait for the element "tr:has-text('<test>') [title='Deactivate']" to appear

    Examples:
      | test             |
      | Exam mode test   |
      | Score shown test |
      | Untried test     |

  Scenario Outline: The learner answers "<test>"
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "<test>"
    And I wait for the page content to settle
    And I start the exercise
    And I wait for the page content to settle
    Then I should see "Question of <test>"
    And I check the "Answer true" radio button
    And I press "Finish test"
    And I wait for the page content to settle
    # The attempt is only completed once the result page is shown: the next scenario reads it from the list.
    Then I should see "Exercise completed"
    And I should not see an error

    Examples:
      | test             |
      | Exam mode test   |
      | Score shown test |

  Scenario: The exercise list hides the score of the exam mode test only
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXAMLIST" homepage
    And I wait for the page to be loaded
    When I follow "Tests"
    And I wait for the page content to settle
    Then the row for "Exam mode test" should contain "Already attempted"
    And the row for "Exam mode test" should not contain "Latest attempt"
    And the row for "Exam mode test" should not contain "%"
    And the row for "Score shown test" should contain "Latest attempt : 100 % (10 / 10)"
    And the row for "Untried test" should contain "Not attempted"

  Scenario: Delete the exam mode list course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXAMLIST"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXAMLIST"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXAMLIST"
