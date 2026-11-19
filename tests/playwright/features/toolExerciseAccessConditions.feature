# A test, typically a final exam, can stay closed until the learner has spent a minimum time in the
# course (and filled in a required profile field). The conditions are the FinalExamAccessRule of
# the test, also written by chamilo:migration:migrate-final-exam-access. They used to be checked
# only by the legacy start page, which now redirects to the Vue one: the Vue start page and the
# runtime API ignored them. They are now checked there too, and teachers set them in the test
# settings while the platform setting exercise.allow_final_exam_access_rule is on.
#
# The JSON of the rule is shown read-only under the fields; "Edit the JSON" freezes the fields
# and saves the JSON as written instead.
#
# How the form values become the stored rule (keys of migrated rules kept, opt-out only with a
# field, identifier format) is pinned by FinalExamAccessRuleTest. The required profile field is
# not driven here: it needs a user extra field that learners can change, which the test platform
# does not have by default.
#
# Uses its own course EXACCESS, deleted at the end, for the same reason as
# toolExerciseTeacher.feature. The @settings-finalExamAccessRule tag restores the setting's
# original value after the last scenario (registerSettingsGuard() in common.steps.ts).
@common @tools @settings-finalExamAccessRule
Feature: Access conditions of a test
  In order to keep a final exam closed until the course has been followed
  As a teacher
  I want learners to start a test only after a minimum time in the course

  Scenario: Create the access conditions test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXACCESS"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=EXACCESS"
    And I wait for the page to be loaded
    Then I should see "EXACCESS"

  Scenario: Subscribe the learner to the access conditions test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
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

  Scenario: The access conditions are hidden while the platform setting is off
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/settings/exercise"
    And I wait for the page to be loaded when ready
    And I select "No" from "form_allow_final_exam_access_rule"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Create exercise"
    And I wait for the page content to settle
    And I press "Advanced settings"
    Then I should see "Prevent moving backwards between questions"
    And I should not see "Access conditions"

  Scenario: Turn on the access conditions
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/settings/exercise"
    And I wait for the page to be loaded when ready
    And I select "Yes" from "form_allow_final_exam_access_rule"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    Then the field "form_allow_final_exam_access_rule" should have value "true"

  Scenario: The teacher creates a test that needs 10 hours in the course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Access conditions test"
    And I press "Advanced settings"
    And I fill in "exercise-final-exam-minimum-minutes" with "600"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Multiple choice"
    And I wait for the page content to settle
    When I fill in "question" with "Question of the access conditions test"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Question of the access conditions test"
    And I should not see an error

  Scenario: The teacher finds the minimum time when coming back
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    And I press "Advanced settings"
    Then the field "exercise-final-exam-minimum-minutes" should have value "600"

  Scenario: The teacher sees the access conditions as JSON
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    When I press "Advanced settings"
    Then the field "exercise-final-exam-json" should contain '"minimum_minutes_before_exam": 600'
    And the field "exercise-final-exam-json" should not be editable
    And the field "exercise-final-exam-minimum-minutes" should be editable

  # The JSON is saved as written, with keys the fields do not edit, like the "source" of the
  # rules written by the migration command.
  @slow-scenario
  Scenario: The teacher edits the access conditions as JSON
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    And I press "Advanced settings"
    When I check "exercise-final-exam-edit-json"
    Then the field "exercise-final-exam-minimum-minutes" should not be editable
    And the field "exercise-final-exam-json" should be editable
    When I fill in "exercise-final-exam-json" with '{"minimum_minutes_before_exam": 900, "user_identifier_field_variable": "", "source": "manual"}'
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "Question of the access conditions test"
    And I should not see an error

  Scenario: The teacher finds the JSON edits when coming back
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    When I press "Advanced settings"
    Then the field "exercise-final-exam-minimum-minutes" should have value "900"
    And the field "exercise-final-exam-json" should contain '"source": "manual"'

  @slow-scenario
  Scenario: An invalid JSON is not saved
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    And I press "Advanced settings"
    And I check "exercise-final-exam-edit-json"
    When I fill in "exercise-final-exam-json" with "{not json"
    And I press "Proceed to questions"
    Then I should see "The JSON of the access conditions must be a JSON object."
    When I fill in "exercise-final-exam-json" with '{"minimum_minutes_before_exam": "900", "user_identifier_field_variable": ""}'
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "must be a whole number of minutes"

  # A new test is visible or hidden depending on the platform's default visibility on creation.
  Scenario: Make the test visible
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Activate']" icon in the row for "Access conditions test" if it is visible
    Then I wait for the element "tr:has-text('Access conditions test') [title='Deactivate']" to appear

  Scenario: The learner cannot start the test before 15 hours in the course
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Access conditions test"
    And I wait for the page content to settle
    Then I wait for the element "#exercise-access-conditions" to appear
    And I should see "more in this course before starting this test."
    And I should not see "Start test"

  Scenario: The teacher removes the minimum time
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Access conditions test"
    And I wait for the page content to settle
    And I press "Advanced settings"
    And I fill in "exercise-final-exam-minimum-minutes" with "0"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should not see an error

  Scenario: The learner can start the test without a minimum time
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXACCESS" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Access conditions test"
    And I wait for the page content to settle
    Then I should see "Start test"
    And I should not see the "#exercise-access-conditions" element

  Scenario: Delete the access conditions test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXACCESS"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXACCESS"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXACCESS"
