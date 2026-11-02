# The "Results page configuration" options of a test (hide the expected answers column, the
# total score, the score of each question...) are only stored when the platform setting
# exercise.allow_quiz_results_page_config is on, as in the legacy Exercise class. The form
# used to show them even when it was off, so a teacher could tick them, save, and find them
# unticked again on the next visit with no explanation. The form now shows them only when
# they can be saved.
#
# Uses its own course EXRESPAGE, deleted at the end, for the same reason as
# toolExerciseTeacher.feature: the shared TEMP course is filled and emptied by other files
# running in parallel. The @settings-exerciseResultsPage tag restores the setting's original
# value after the last scenario (registerSettingsGuard() in common.steps.ts).
@common @tools @settings-exerciseResultsPage
Feature: Results page configuration of a test
  In order to control what learners see after a test
  As a teacher
  I want the results page options I tick to be kept

  Scenario: Create the results page test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXRESPAGE"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=EXRESPAGE"
    And I wait for the page to be loaded
    Then I should see "EXRESPAGE"

  Scenario: Subscribe the teacher to the results page test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I press "Teachers"
    And the URL should contain "type=1"
    And I click the "[title='Add']" element
    And the URL should contain "type=1"
    And I wait for the page to be loaded
    And I fill in the following:
      | search | mmosquera |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Mosquera"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: The results page options are hidden while the platform setting is off
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/settings/exercise"
    And I wait for the page to be loaded when ready
    And I select "No" from "form_allow_quiz_results_page_config"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I follow "Create exercise"
    And I wait for the page content to settle
    And I press "Advanced settings"
    Then I should see "Show score to learner"
    And I should not see "Results page configuration"

  Scenario: Turn on the results page configuration
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/settings/exercise"
    And I wait for the page to be loaded when ready
    And I select "Yes" from "form_allow_quiz_results_page_config"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    Then the field "form_allow_quiz_results_page_config" should have value "true"

  Scenario: The teacher's results page options are kept after saving
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Results page test"
    And I press "Advanced settings"
    And I check "hideExpectedAnswers"
    And I check "hideTotalScore"
    And I check "hideQuestionScore"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"
    When I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Results page test"
    And I wait for the page content to settle
    Then the field "title" should have value "Results page test"
    And I press "Advanced settings"
    And the checkbox "hideExpectedAnswers" should be checked
    And the checkbox "hideTotalScore" should be checked
    And the checkbox "hideQuestionScore" should be checked
    And the checkbox "hideCategoryTable" should not be checked

  Scenario: The administrator can untick one results page option
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Results page test"
    And I wait for the page content to settle
    And the field "title" should have value "Results page test"
    And I press "Advanced settings"
    And the checkbox "hideTotalScore" should be checked
    When I uncheck "hideTotalScore"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"
    When I am on course "EXRESPAGE" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Results page test"
    And I wait for the page content to settle
    Then the field "title" should have value "Results page test"
    And I press "Advanced settings"
    And the checkbox "hideExpectedAnswers" should be checked
    And the checkbox "hideTotalScore" should not be checked
    And the checkbox "hideQuestionScore" should be checked

  Scenario: Delete the results page test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXRESPAGE"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXRESPAGE"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXRESPAGE"
