# A learner could still start and take a test of a session that was over and whose "Visibility after
# end date" is "Read only": the exercise page showed "Start test", and the attempt was accepted. The
# exercise page now hides the button and says "The session is read only", and the runtime API refuses
# to start, answer or finish an attempt (ExerciseRuntime*Processor). When the visibility after end
# date is "Accessible", the learner keeps taking the test as before.
#
# Verified by reading, not live: session_add.php / session_edit.php are the legacy FormValidator
# forms already driven by specialCase1Sessions.feature (hidden date fields) and
# sessionManagement.feature (edit page reached from the session's own page), and "Visibility after
# end date" is their select "session_visibility" ("Read only" / "Accessible" / "Not accessible").
# The session dates are in 2020 so that it is over whatever day the suite runs.
#
# Uses its own course EXROSESSION and session, deleted at the end, for the same reason as
# toolExerciseTeacher.feature: the shared TEMP course is filled and emptied by other files running
# in parallel.
@common @tools
Feature: Tests of a session that is over and read only
  In order to keep the results of a finished session frozen
  As a learner
  I must not be able to take a test once the session is over and read only

  Scenario: Create the read only session test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXROSESSION"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=EXROSESSION"
    And I wait for the page to be loaded
    Then I should see "EXROSESSION"

  Scenario: Create a test with one question
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXROSESSION" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Read only session test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Multiple choice"
    And I wait for the page content to settle
    When I fill in "question" with "Question of the read only session test"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Question of the read only session test"
    And I should not see an error

  # A new test is visible to learners from the start: "Default visibility of new tests" is "visible"
  # by default (ResourceDefaultVisibilityHelper). Check it instead of activating it.
  Scenario: The test is visible
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXROSESSION" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    Then I wait for the element "tr:has-text('Read only session test') [title='Deactivate']" to appear

  Scenario: Create a session that is over and read only, with the course and the learner
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    And I click the "#advanced_params" element
    And I fill in the following:
      | title | Read only exercise session |
    And I set hidden field "access_start_date" to "2020-01-01 00:00"
    And I set hidden field "display_start_date" to "2020-01-01 00:00"
    And I set hidden field "coach_access_start_date" to "2020-01-01 00:00"
    And I set hidden field "access_end_date" to "2020-01-31 00:00"
    And I set hidden field "display_end_date" to "2020-01-31 00:00"
    And I set hidden field "coach_access_end_date" to "2020-01-31 00:00"
    And I select "Read only" from "session_visibility"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session"
    And I select "EXROSESSION" from the ajax select "courses"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Subscribe users to this session"
    And I select "acostea" from the ajax select "users"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Read only exercise session"
    And I should see "acostea"

  Scenario: The learner cannot start the test of the read only session
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXROSESSION" homepage in session "Read only exercise session"
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Read only session test"
    And I wait for the page content to settle
    Then I should see "The session is read only"
    And I should not see "Start test"
    And starting the exercise of this page should be refused with "The session is read only"

  Scenario: Make the session accessible after its end date
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Read+only+exercise+session"
    And I wait for the page to be loaded
    When I follow "Read only exercise session"
    And I wait for the page to be loaded
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "Advanced settings"
    And I select "Accessible" from "session_visibility"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Read only exercise session"

  Scenario: The learner can take the test of a session that stays accessible
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXROSESSION" homepage in session "Read only exercise session"
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Read only session test"
    And I wait for the page content to settle
    Then I should not see "The session is read only"
    And I start the exercise
    And I wait for the page content to settle
    Then I should see "Question of the read only session test"
    And I check the "Answer true" radio button
    And I press "Finish test"
    And I wait for the page content to settle
    Then I should not see an error

  Scenario: Delete the read only session
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Read+only+exercise+session"
    And wait for the page to be loaded
    And I click the ".mdi-delete" icon in the row for "Read only exercise session"
    And I press "Yes"
    And wait for the page to be loaded
    Then I should not see "Read only exercise session"
    And I should not see an error

  Scenario: Delete the read only session test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXROSESSION"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXROSESSION"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXROSESSION"
