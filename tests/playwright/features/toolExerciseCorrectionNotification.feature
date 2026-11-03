# When the teacher ticks "Send email" on the correction page of an attempt, the message is
# pre-filled as in 1.11 (exercise_show.php): the course, the test and a link to the corrected
# attempt. The link follows the context of the attempt: the result page of the Tests tool, or
# the learning path item when the attempt was made inside a learning path. It used to be
# pre-filled with the teacher's comment only.
#
# Uses its own course EXCORRMAIL, deleted at the end, for the same reason as
# toolExerciseTeacher.feature: the shared TEMP course is filled and emptied by other files
# running in parallel. The course is created in English so that the learner's pages render in
# English whatever their own language.
@common @tools
Feature: Correction notification of a test attempt
  In order to tell a learner where to find their correction
  As a teacher
  I want the correction email to be pre-filled with a link to the corrected attempt

  Scenario: Create the correction notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXCORRMAIL"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=EXCORRMAIL"
    And I wait for the page to be loaded
    Then I should see "EXCORRMAIL"

  Scenario: Subscribe the learner to the correction notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
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

  Scenario: Create a test with an open question
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Correction mail test"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    And I fill in "question" with "Describe your correction"
    And I fill in "exercise-manual-question-score" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Describe your correction"

  # A new test is hidden from learners until it is activated.
  Scenario: Make the test visible
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Activate']" icon in the row for "Correction mail test"
    Then I wait for the element "tr:has-text('Correction mail test') [title='Deactivate']" to appear

  Scenario: The learner answers the test in the Tests tool
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Correction mail test"
    And I wait for the page content to settle
    And I start the exercise
    And I wait for the page content to settle
    And I fill in the open answer with "Answer given in the Tests tool"
    And I press "Finish test"
    And I wait for the page content to settle
    Then I should see "This answer is pending teacher correction."

  # The correction is saved afterwards, so that the attempt made in the learning path below is
  # the only one left to correct.
  Scenario: The email of an attempt made in the Tests tool links to its result page
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Results']" icon in the row for "Correction mail test"
    And I wait for the page content to settle
    And I click the "[title='Correct']" icon in the row for "Costea"
    And I wait for the page content to settle
    Then I should see "Answer given in the Tests tool"
    When I check "send_notification"
    Then the active tinymce editor should contain "EXCORRMAIL"
    And the active tinymce editor should contain "Correction mail test"
    And the active tinymce editor should contain "Click the link below"
    And the active tinymce editor should contain "/result/"
    When I uncheck "send_notification"
    And I press "Save correction"
    And I wait for the page content to settle
    Then I should not see an error

  Scenario: Create a learning path holding the test
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    When I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Create new learning path"
    And I wait for the page to be loaded
    And I fill in "lp-title" with "Correction mail path"
    And I press "Continue"
    And I wait for the page to be loaded
    And I switch the LP resource panel to "Tests"
    And I add LP item "Correction mail test" from the resource panel
    And I wait for the page content to settle
    Then I should see "Correction mail test"

  # A new learning path is hidden from learners until it is shown.
  Scenario: Make the learning path visible to learners
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I press "Show"
    Then I should see an icon with title "Hide"

  Scenario: The learner answers the test inside the learning path
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Learning paths"
    And I wait for the page content to settle
    And I follow "Correction mail path"
    When I open the current learning path item in the page
    And I start the exercise
    And I wait for the page content to settle
    And I fill in the open answer with "Answer given in the learning path"
    And I press "Finish test"
    And I wait for the page content to settle
    Then I should see "This answer is pending teacher correction."

  Scenario: The email of an attempt made in a learning path links to the learning path item
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXCORRMAIL" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Results']" icon in the row for "Correction mail test"
    And I wait for the page content to settle
    And I click the "[title='Correct']" icon in the row for "Costea"
    And I wait for the page content to settle
    Then I should see "Answer given in the learning path"
    When I check "send_notification"
    Then the active tinymce editor should contain "EXCORRMAIL"
    And the active tinymce editor should contain "Correction mail test"
    And the active tinymce editor should contain "/runtime?"
    And the active tinymce editor should contain "item_id="

  Scenario: Delete the correction notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXCORRMAIL"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXCORRMAIL"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXCORRMAIL"
