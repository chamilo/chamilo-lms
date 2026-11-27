# Covers /reporting/admin/works-in-session, the port of the 1.11 main/mySpace/works_in_session_report.php: one row
# per learner of the selected session, then the best score, progress and last sent work date of each course, headed
# by the course code.
#
# Uses its own course WISREPORT and session, deleted at the end. The administrator creates the session with
# mmosquera ("I am a teacher") as general coach, so the coach view is checked with the same data. The assignment is
# created inside the session, so that acostea's submission is a work of the session.
@common
Feature: Works in session report
  In order to follow the learners of a session across its courses
  As a platform administrator or a coach of the session
  I need one row per learner with the score, progress and last sent work of each course

  Scenario: Create the course of the report
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "WISREPORT"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    And I am on "/admin/course-list?keyword=WISREPORT"
    And I wait for the page to be loaded
    Then I should see "WISREPORT"

  # Three legacy forms in a row (session, courses, users): more than the default 90 seconds on a loaded runner.
  @slow-scenario
  Scenario: Create the session with the course, its coach and a learner
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Works in session report test |
    And I select "mmosquera" from the ajax select "coach_username"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session"
    And I select "WISREPORT" from the ajax select "courses"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Subscribe users to this session"
    And I select "acostea" from the ajax select "users"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Works in session report test"
    And I should see "acostea"

  @slow-scenario
  Scenario: Create an assignment in the session
    Given I am a platform administrator
    And I am on course "WISREPORT" homepage in session "Works in session report test"
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I press "Create Assignment"
    And wait for the page to be loaded
    When I fill in the following:
      | Assignment name | Session report work |
    And I fill in the active tinymce editor with "Work of the session"
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Assignment created"

  @slow-scenario
  Scenario: The learner sends a work in the session
    Given I am not logged
    Given I am a student
    And I am on course "WISREPORT" homepage in session "Works in session report test"
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I follow "Session report work"
    And wait for the page to be loaded when ready
    Then I press "Upload file"
    And wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to the upload dropzone
    And I press "Upload file"
    And wait for the page to be loaded when ready
    # The "File uploaded successfully" toast can be gone before a slow runner checks it. The administrator scenario
    # below proves the submission: the last sent work date of the learner is filled.
    Then I should not see an error

  # Without a session the 1.11 page only showed the session selector: nothing is listed and nothing can be exported.
  Scenario: The report asks for a session before showing anything
    Given I am not logged
    Given I am a platform administrator
    When I am on "/reporting/admin/works-in-session"
    And I wait up to 60 seconds for the element ".empty-state" to appear
    Then I should see "Works in session report"
    And I should see "Please select a session"
    And I should not see the "button[aria-label='Export to CSV']" element
    And I should not see the "button[aria-label='Export to XLS']" element
    And I should not see an error

  Scenario: The administrator sees one row per learner and three columns per course
    Given I am not logged
    Given I am a platform administrator
    And I am on "/reporting/admin/works-in-session"
    And I wait up to 60 seconds for the element ".empty-state" to appear
    When I select the dropdown option "Works in session report test" in "global-reporting-session"
    And I press "Search"
    And I wait for the element "td:has-text('Andrea')" to appear
    # The three columns of a course share a header with the course code.
    Then I should see the "th:has-text('WISREPORT')" element
    And I should see "Best score"
    And I should see "Progress"
    And I should see "Last sent work date"
    And I should see "Costea"
    And I should see the "button[aria-label='Export to CSV']" element
    And I should see the "button[aria-label='Export to XLS']" element
    And I should not see "Please select a session"
    # The last column is the last sent work date of the course: it holds the date of the work (a year "20..")
    # instead of "-".
    And I should see the "tr:has-text('Costea') td:last-child:has-text('20')" element
    And I should not see an error

  Scenario: The general coach of the session sees the same learner
    Given I am not logged
    Given I am a teacher
    And I am on "/reporting/admin/works-in-session"
    And I wait up to 60 seconds for the element ".empty-state" to appear
    When I select the dropdown option "Works in session report test" in "global-reporting-session"
    And I press "Search"
    And I wait for the element "td:has-text('Andrea')" to appear
    Then I should see the "th:has-text('WISREPORT')" element
    And I should see "Costea"
    And I should not see an error

  Scenario: The old legacy page redirects to the report
    Given I am not logged
    Given I am a platform administrator
    When I am on "/main/my_space/works_in_session_report.php"
    And I wait up to 60 seconds for the element ".empty-state" to appear
    Then the URL should contain "/reporting/admin/works-in-session"
    And I should see "Works in session report"

  Scenario: Delete the course of the report
    Given I am not logged
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=WISREPORT"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "WISREPORT"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "WISREPORT"

  Scenario: Delete the session of the report
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Works+in+session+report+test"
    And wait for the page to be loaded
    And I click the ".mdi-delete" icon in the row for "Works in session report test"
    And I press "Yes"
    And wait for the page to be loaded
    Then I should not see "Works in session report test"
    And I should not see an error
