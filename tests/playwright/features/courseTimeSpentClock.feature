# Learners can see, on every page of a course including the learning path player, a clock with the
# time they spent in the course, as counted by the course tracking. The administrator shows it on
# the whole platform, or lets each course choose.
#
# The platform value is global and other files run in parallel: showing the clock platform-wide
# would add a fixed element to every course page those files use. So this file keeps the platform
# value untouched and checks the course-level choice in its own course, TSCLOCK, which it deletes
# at the end. Letting courses choose changes nothing for a course that has not chosen, so it does
# not disturb the other files either. How the platform value, the course value and the "can be
# changed by course" switch combine is pinned by CourseTimeSpentClockHelperTest.
@common @tools
Feature: Clock of the time spent in the course
  In order to know how long they have been studying a course
  As a learner
  I want to see the time I spent in the course on every page of the course

  Scenario: The administrator finds the time spent clock settings, disabled by default
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=show_clock_time_spent"
    And I wait for the page to be loaded
    Then I should see "Show learners the time spent in the course"
    And I should see "Time spent clock can be changed by course"
    And the field "form_show_clock_time_spent_on_all_course_page_for_learner" should have value "false"
    And the field "form_show_clock_time_spent_can_be_modified_in_course" should have value "false"

  Scenario: A teacher cannot change the platform settings
    Given I am a teacher
    And I wait for the page to be loaded
    When I am on "/admin/settings/course"
    And I wait for the page to be loaded
    Then I should not see "Time spent clock can be changed by course"

  @long-scenario
  Scenario: Create the time spent clock course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "TSCLOCK"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    # The course list is sorted by title and paginated: search the course rather than expecting
    # it on the first page.
    And I am on "/admin/course-list?keyword=TSCLOCK"
    And I wait for the page to be loaded
    Then I wait up to 60 seconds for the element "text=TSCLOCK" to appear

  Scenario: Subscribe the teacher to the time spent clock course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TSCLOCK" homepage
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

  Scenario: Subscribe the learner to the time spent clock course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TSCLOCK" homepage
    And I wait for the page to be loaded
    And I press "Show all"
    And I wait for the page content to settle
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

  Scenario: The learner sees no clock while nothing shows it
    Given I am a student
    And I wait for the page to be loaded
    When I am on course "TSCLOCK" homepage
    And I wait for the page content to settle
    Then I should not see the "#course-time-spent-clock" element

  Scenario: The course cannot choose while the administrator does not allow it
    Given I am a teacher
    And I wait for the page to be loaded
    When I am on the settings form of course "TSCLOCK"
    And I wait up to 30 seconds for the element "text=Save settings" to appear
    Then I should not see "Show learners the time spent in the course"

  @long-scenario
  Scenario: The administrator lets each course choose whether it shows the clock
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=show_clock_time_spent_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "Yes" from "form_show_clock_time_spent_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=show_clock_time_spent_can_be_modified_in_course"
    And I wait for the page to be loaded
    Then the field "form_show_clock_time_spent_can_be_modified_in_course" should have value "true"

  # The course follows the platform until the teacher chooses: "Use the platform setting" must be
  # the checked option, otherwise saving the form would not keep that choice visible.
  @long-scenario
  Scenario: The teacher shows the clock in the course
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on the settings form of course "TSCLOCK"
    And I wait up to 30 seconds for the element "details:has(input[name='show_clock_time_spent_on_all_course_page_for_learner']) > summary" to appear
    And I click the "details:has(input[name='show_clock_time_spent_on_all_course_page_for_learner']) > summary" element
    And the "show_clock_time_spent_on_all_course_page_for_learner" radio button with "platform" value should be checked
    When I check the "show_clock_time_spent_on_all_course_page_for_learner" radio button with "true" value
    And I press "Save settings"
    And I wait for the page content to settle
    And I am on the settings form of course "TSCLOCK"
    And I wait up to 30 seconds for the element "details:has(input[name='show_clock_time_spent_on_all_course_page_for_learner']) > summary" to appear
    Then the "show_clock_time_spent_on_all_course_page_for_learner" radio button with "true" value should be checked

  Scenario: The teacher does not see the clock
    Given I am a teacher
    And I wait for the page to be loaded
    When I am on course "TSCLOCK" homepage
    And I wait for the page content to settle
    Then I should not see the "#course-time-spent-clock" element

  @long-scenario
  Scenario: The teacher creates a learning path with a page
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "TSCLOCK" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Create new learning path"
    And I wait for the page content to settle
    And I fill in "title" with "Tsclock path"
    And I press "Continue"
    And I wait for the page content to settle
    When I press "Create a new document"
    And I fill in "Title" with "Tsclock page"
    And I fill in the active tinymce editor with "Time spent clock content"
    And I press "Save"
    And I wait for the page content to settle
    And I am on course "TSCLOCK" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait up to 30 seconds for the element "text=Tsclock path" to appear
    And I press "Show"
    And I wait for the page content to settle
    Then I should see "Tsclock path"

  @long-scenario
  Scenario: The learner sees the clock on the course pages and in the learning path
    Given I am a student
    And I wait for the page to be loaded
    When I am on course "TSCLOCK" homepage
    Then I wait up to 60 seconds for the element "#course-time-spent-clock" to appear
    When I follow "Announcements"
    Then I wait up to 60 seconds for the element "#course-time-spent-clock" to appear
    When I am on course "TSCLOCK" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait up to 30 seconds for the element "text=Tsclock path" to appear
    And I follow "Tsclock path"
    And I wait up to 60 seconds for the element "text=Tsclock page" to appear
    Then I wait up to 60 seconds for the element "#course-time-spent-clock" to appear

  @long-scenario
  Scenario: Teardown — the courses can no longer choose, and the course is deleted
    Given I am a platform administrator
    And I wait for the page to be loaded
    When I am on "/admin/settings/search_settings?keyword=show_clock_time_spent_can_be_modified_in_course"
    And I wait for the page to be loaded
    And I select "No" from "form_show_clock_time_spent_can_be_modified_in_course"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=TSCLOCK"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "TSCLOCK"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "TSCLOCK"
