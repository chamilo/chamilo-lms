# The student view used to be driven by an isStudentView URL parameter that every tool
# forwarded to its own API calls. It is now a single session state, toggled only by the
# button in the course tool header (StudentViewButton.vue, mounted by SectionHeader).
#
# What these scenarios pin is precisely what used to be broken: the toggle changing what
# the user sees WITHOUT a navigation. The views watched route.query.isStudentView, which
# never changes when the button is pressed, so the server-computed canManage stayed frozen
# from the initial load and the button did nothing. Hence there is deliberately no
# "I am on ..." step between pressing the button and asserting.
#
# Selector note, confirmed live: the button is a BaseToggleButton, and BaseButton renders
# the MDI icon as a ligature glyph INSIDE the <button>, so its accessible name is
# "󰈉 Switch to student view" — the glyph, then the label. The shared "I press" step tries
# getByRole("button", { name, exact: true }) before falling back to a substring match, and
# the exact tier therefore misses. These scenarios click ".studentview-button" instead,
# which is the class the component sets on itself, and assert the label separately with
# "I should see". Do not "simplify" them back to `I press "Switch to student view"`.
#
# Roles: the platform administrator reaches the button through ROLE_ADMIN and the teacher
# through ROLE_CURRENT_COURSE_TEACHER. Both are worth running, because only the teacher
# exercises the alignment between the button's own visibility rule
# (securityStore.isCourseAdmin) and the backend gate on /toggle_student_view. A session
# coach is the third role with access, but no session-coach fixture user exists in the
# shared seeds, so that path is not covered here.
@common @tools
Feature: Student view
  In order to confirm what a learner will see
  As a teacher
  I want one button that switches my own permissions without leaving the page

  # TEMP is a shared fixture, but the seed only subscribes the administrator, so the
  # teacher and student scenarios below subscribe their own users first — the same
  # subscribe-before-using-the-tool convention toolExerciseTeacher.feature and
  # toolReporting.feature follow. These two are NOT idempotent: run against a user who is
  # already subscribed, the Users tool takes a different path and they fail. They are setup,
  # so run them once on a fresh TEMP, or skip them when the subscriptions already exist.
  Scenario: Subscribe the teacher to the course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I press "Teachers"
    And I wait for the page content to settle
    And I click the "[title='Add']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | search | mmosquera |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Mosquera"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: Subscribe the student to the course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
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

  Scenario: The administrator loses the course description actions in the student view
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Course description"
    And I wait for the page to be loaded
    Then I should see the "span.mdi-image-text" element
    When I click the ".studentview-button" element
    And I wait for the page content to settle
    Then I should not see the "span.mdi-image-text" element
    And I should see "Switch to teacher view"
    When I click the ".studentview-button" element
    And I wait for the page content to settle
    Then I should see the "span.mdi-image-text" element
    And I should see "Switch to student view"

  Scenario: The teacher loses the course description actions in the student view
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Course description"
    And I wait for the page to be loaded
    And I wait up to 60 seconds for the element "span.mdi-image-text" to appear
    When I click the ".studentview-button" element
    And I wait for the page content to settle
    Then I should not see the "span.mdi-image-text" element
    When I click the ".studentview-button" element
    And I wait up to 60 seconds for the element "span.mdi-image-text" to appear

  # Forum is the regression guard for the phase that deleted forumService's blanket
  # injection of the parameter: its views had no watcher at all, and what the student view
  # changes there is server-side (the collection providers filter hidden categories,
  # forums, threads and posts), so the list itself has to be refetched.
  Scenario: The teacher loses the forum management actions in the student view
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    # "Forum", singular: AbstractTool::getTitleToShow() has no plural case for it, so the
    # tile label is ucfirst('forum').
    And I follow the course tool "Forum"
    And I wait for the page content to settle
    Then I should see "Add a category"
    When I click the ".studentview-button" element
    And I wait for the page content to settle
    Then I should not see "Add a category"
    When I click the ".studentview-button" element
    And I wait for the page content to settle
    Then I should see "Add a category"

  # The switch only exists where a SectionHeader is mounted, so a tool whose landing view
  # has no header silently has no switch at all — which is exactly how most course tools
  # ended up without it. Presence per tool is therefore its own regression guard, separate
  # from the toggle behaviour pinned above; it is asserted for every course tool the teacher
  # can reach except Reporting, which is deliberately left out (entering the student view
  # revokes the very permission that grants access to it).
  #
  # The URL step comes first on purpose: the tool card the scenario just clicked is a
  # client-side route change, and the course homepage it leaves ALSO has the switch (mounted
  # by CourseHome's own SectionHeader), so asserting the label straight away would pass
  # against the old page. Waiting for the destination URL — auto-retrying — is what makes
  # the following assertion belong to the tool page.
  Scenario Outline: The teacher sees the student view switch in the <tool> tool
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "<tool>"
    Then the URL should contain "<path>"
    And I should see "Switch to student view"

    Examples:
      | tool               | path                           |
      | Agenda             | /resources/ccalendarevent      |
      | Announcements      | /resources/announcement/       |
      | Assignments        | /resources/assignment/         |
      | Attendances        | /resources/attendance/         |
      | Course description | /resources/course-description/ |
      | Course progress    | /resources/course-progress/    |
      | Documents          | /resources/document/           |
      | Dropbox            | /resources/dropbox/            |
      | Tests              | /resources/exercise/           |
      | Forum              | /resources/forum/              |
      | Glossary           | /resources/glossary/           |
      | Assessments        | /resources/gradebook/          |
      | Groups             | /resources/course-users/       |
      | Learning paths     | /resources/lp/                 |
      | Links              | /resources/links/              |
      | Users              | /resources/course-users/       |
      | Notebook           | /resources/notebook/           |
      | Portfolio          | /resources/portfolio/          |
      | Surveys            | /resources/survey/             |
      | Wiki               | /resources/wiki/               |

  # Pins the button's own visibility rule: it is gated on securityStore.isCourseAdmin,
  # which is exactly the backend gate of /toggle_student_view, so a learner never sees it.
  # The "Course description" assertion is what stops this passing vacuously: without it, a
  # student who could not reach the course at all would also "not see" the button.
  Scenario: The student never sees the switch
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I should see "Course description"
    And I should not see "Switch to student view"
    And I should not see "Switch to teacher view"
