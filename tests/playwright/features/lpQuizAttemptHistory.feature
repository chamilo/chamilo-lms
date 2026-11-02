# A test added to a learning path opens on its overview page, as the legacy start page did:
# the learner sees the attempts already made in this learning path and can open each one to
# read its correction. The runtime used to open the player directly, which has neither.
#
# Uses its own course LPQUIZHIST, deleted at the end (which also removes its test and its
# learning path), for the same reason as toolExerciseNotification.feature: the shared TEMP
# course is filled and emptied by other files running in parallel. The course is created in
# English so that the learner's course pages render in English whatever their own language.
@common @tools
Feature: Test attempts inside a learning path
  In order to review my corrections
  As a learner
  I want to see my previous attempts when I open a test from a learning path

  Scenario: Create the learning path test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "LPQUIZHIST"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    # Saving lands on the paginated course list, where the new course is not always on the
    # first page.
    And I am on "/admin/course-list?keyword=LPQUIZHIST"
    And I wait for the page to be loaded
    Then I should see "LPQUIZHIST"

  Scenario: Subscribe the learner to the learning path test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
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

  Scenario: Create a test with an open question
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Learning path quiz"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    When I fill in "question" with "Describe the learning path"
    And I fill in "exercise-manual-question-score" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Describe the learning path"

  # A new test is hidden, and the learning path builder only lists visible ones (unless
  # lp.show_invisible_exercise_in_lp_list is on).
  Scenario: Make the test visible
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    When I click the "[title='Activate']" icon in the row for "Learning path quiz"
    Then I wait for the element "tr:has-text('Learning path quiz') [title='Deactivate']" to appear

  Scenario: Create a learning path holding the test
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    When I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Create new learning path"
    And I wait for the page to be loaded
    And I fill in "lp-title" with "Quiz learning path"
    And I press "Continue"
    And I wait for the page to be loaded
    And I switch the LP resource panel to "Tests"
    And I add LP item "Learning path quiz" from the resource panel
    And I wait for the page content to settle
    Then I should see "Learning path quiz"

  # A new learning path is hidden from learners until it is shown.
  Scenario: Make the learning path visible to learners
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    When I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Show"
    Then I should see an icon with title "Hide"

  Scenario: The learner takes the test inside the learning path
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I follow "Quiz learning path"
    When I open the current learning path item in the page
    And I start the exercise
    And I wait for the page content to settle
    Then I should see "Describe the learning path"
    And I fill in the open answer with "Answer given inside the learning path"
    And I press "Finish test"
    And I wait for the page content to settle
    # Inside a learning path, finishing lands straight on the attempt result, without the
    # "Exercise completed" banner shown outside one.
    Then I should see "This answer is pending teacher correction."

  Scenario: The learner finds the attempt and its correction when coming back to the test
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "LPQUIZHIST" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I follow "Quiz learning path"
    When I open the current learning path item in the page
    Then I should see "Attempt history"
    And I should see "You have tried to resolve this exercise earlier"
    When I click the "a[href*='/result/']" element
    And I wait for the page content to settle
    Then I should see "Describe the learning path"
    And I should see "Answer given inside the learning path"

  Scenario: Delete the learning path test course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=LPQUIZHIST"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "LPQUIZHIST"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "LPQUIZHIST"
