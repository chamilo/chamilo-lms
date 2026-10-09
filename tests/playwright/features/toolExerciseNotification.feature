# The course setting "E-mail alerts > Tests" (email_alert_manager_on_new_quiz) tells the teacher
# when a learner finishes an exercise, and separately when the learner answered an open question
# that needs correcting. Those notifications were lost when the exercise player moved to Vue,
# so a teacher could leave an open answer uncorrected without ever knowing it was there.
#
# This file walks the real flow end to end: the teacher turns the two options on, a learner
# answers an open question, and the teacher finds both notifications in the inbox, with the
# answer itself and the link to correct it. Who receives them inside a session (course coaches
# and general coaches), the start and oral-question options, the exercise-level override and
# the "teacher takes the test" exclusion are pinned by ExerciseNotificationManagerTest.
#
# Uses its own course EXNOTIF, deleted at the end, for the same reason as
# toolExerciseTeacher.feature: the shared TEMP course is filled and emptied by other files
# running in parallel. The inbox delete only marks the messages deleted for their receiver, as
# studentBossReport.feature notes: the message rows themselves stay.
@common @tools
Feature: Exercise notifications to the teacher
  In order to correct open answers without delay
  As a teacher
  I want to be notified when a learner finishes an exercise or answers an open question

  Scenario: Create the exercise notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXNOTIF"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I should see "EXNOTIF"

  Scenario: Subscribe the teacher to the notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXNOTIF" homepage
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

  Scenario: Subscribe the learner to the notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXNOTIF" homepage
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

  Scenario: The teacher asks to be told about finished exercises and open answers
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on the settings form of course "EXNOTIF"
    # The notification options sit in a collapsed <details> panel of the form.
    And I wait up to 30 seconds for the element "details:has(#email_alert_manager_on_new_quiz_1) > summary" to appear
    And I click the "details:has(#email_alert_manager_on_new_quiz_1) > summary" element
    When I check "email_alert_manager_on_new_quiz_1"
    And I check "email_alert_manager_on_new_quiz_3"
    And I press "Save settings"
    And I wait for the page content to settle
    And I am on the settings form of course "EXNOTIF"
    And I wait up to 30 seconds for the element "details:has(#email_alert_manager_on_new_quiz_1) > summary" to appear
    Then the checkbox "email_alert_manager_on_new_quiz_1" should be checked
    And the checkbox "email_alert_manager_on_new_quiz_3" should be checked
    And the checkbox "email_alert_manager_on_new_quiz_2" should not be checked

  Scenario: Create an exercise with an open question
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXNOTIF" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Notification exercise"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    When I fill in "question" with "Describe your notification"
    And I fill in "exercise-manual-question-score" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Describe your notification"

  Scenario: The learner answers the open question
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXNOTIF" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Notification exercise"
    And I start the exercise
    And I wait for the page content to settle
    Then I should see "Describe your notification"
    And I fill in the open answer with "Answer waiting for the teacher"
    And I press "Finish test"
    And I wait for the page content to settle
    Then I should see "Exercise completed"

  # The open-answer notification carries what the teacher needs to act: the exercise, the
  # learner's answer and the link to the correction page.
  Scenario: The teacher is told about the open answer and removes the notification
    Given I am a teacher
    And I am on "/resources/messages"
    And I wait up to 30 seconds for the element "tr:has-text('A learner has answered an open question')" to appear
    When I follow "A learner has answered an open question"
    And I wait for the page content to settle
    Then I should see "Notification exercise"
    And I should see "Answer waiting for the teacher"
    And I should see "Click this link to check the answer and/or give feedback"
    And I am on "/resources/messages"
    And I wait up to 30 seconds for the element "tr:has-text('A learner has answered an open question')" to appear
    And I click the "[title='Delete']" icon in the row for "A learner has answered an open question"
    And I press "Yes"
    Then I wait until I no longer see "A learner has answered an open question"

  Scenario: The teacher is told the exercise was finished and removes the notification
    Given I am a teacher
    And I am on "/resources/messages"
    And I wait up to 30 seconds for the element "tr:has-text('A learner attempted an exercise')" to appear
    When I follow "A learner attempted an exercise"
    And I wait for the page content to settle
    Then I should see "Notification exercise"
    And I should see "Costea"
    And I am on "/resources/messages"
    And I wait up to 30 seconds for the element "tr:has-text('A learner attempted an exercise')" to appear
    And I click the "[title='Delete']" icon in the row for "A learner attempted an exercise"
    And I press "Yes"
    Then I wait until I no longer see "A learner attempted an exercise"

  Scenario: Delete the exercise notification course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXNOTIF"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXNOTIF"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXNOTIF"
