# Issue #1643: on the test results page (exercise_report.php), choosing the
# "Not validated" status to see only the attempts still to correct was lost as
# soon as the teacher opened an attempt and came back to the list: the list
# showed all attempts again. The selected status must now be kept.
#
# Self-contained: works on its own course (EXRPTF) and deletes it at the end,
# so it never depends on, or changes, the shared TEMP course.
@common @tools
Feature: Test results status filter
  Teachers filter the results of a test to correct the attempts one by one

  Scenario: Create a course with a test
    Given I am a platform administrator
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXRPTF"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I should see "EXRPTF"
    Given I am on course "EXRPTF" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Filter test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"

  Scenario: The status filter is kept when coming back to the results
    Given I am a platform administrator
    And I am on course "EXRPTF" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Filter test"
    And I wait for the page content to settle
    And the URL should contain "/questions"
    When I go to the legacy results page of the current test
    And I wait for the page to be loaded
    And I save current URL with name "exrptf-results"
    And I select "Not validated" from "gs_results_status"
    And I wait for the page content to settle
    And I am on course "EXRPTF" homepage
    And I wait for the page to be loaded
    And I visit URL saved with name "exrptf-results"
    And I wait for the page to be loaded
    Then the field "gs_results_status" should have value "0"

  Scenario: Delete the course
    Given I am a platform administrator
    And I am on "/admin/course-list?keyword=EXRPTF"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXRPTF"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXRPTF"
