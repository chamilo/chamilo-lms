# features/courseTools.feature#
# This test leaves course cid=3 titled "TEMP" available for other tests
@common @tools
Feature: Course tools basic testing
  In order to use a course
  As a teacher
  I need to be able to enter a course and each of its tools


  Background:
    Given I am a platform administrator


  Scenario: See the courses list
    Given I am on "/admin/course-list"
    And I wait for the page to be loaded
    And I should not see "not authorized"


  Scenario: See the course creation link on the admin page
    Given I am on "/main/admin/index.php"
    And I wait for the page to be loaded
    And I wait for the page to be loaded
    Then I should see "Add course"


  Scenario: Create a course before testing
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "TEMP"
    # Not in the original Behat scenario: course.course_creation_form_set_
    # course_category_mandatory is off by default on a fresh install (so the
    # original, title-only version works there), but is enabled on this
    # shared dev box specifically — without a category, submit silently
    # re-renders the same form with a validation error instead of creating
    # anything. Filling it defensively works either way (mandatory or not).
    And I select "Language skills" from the ajax select "update_course_course_categories"
    # Also not in the original: this platform's default course language is
    # French, so an unselected Language field silently created "TEMP" as a
    # French-language course — every subsequent tool label ("Documents"
    # aside, a coincidental cognate) then rendered in French, breaking every
    # "I follow" step that looks for the English tool name.
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I should see "TEMP"


  Scenario: Make sure the course exists
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the course description tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Course description"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the documents tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Documents"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the learning path tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/lp/lp_controller.php?action=list&cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the links tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/link/link.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the tests tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/exercise/exercise.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the announcements tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/announcements/announcements.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the assessments tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/gradebook/index.php?cid=3"
    # Original Behat text was "wait the page to be loaded when ready" (missing
    # "for") — no such step was ever defined in FeatureContext.php, so this
    # scenario always errored out at this exact line in the original suite
    # and never actually reached "Then I should not see an error". Fixed the
    # typo here to restore the check it was clearly meant to perform.
    And wait for the page to be loaded when ready
    Then I should not see an error


  Scenario: Make sure the glossary tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/glossary/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the attendances tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the course progress tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Course progress"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the agenda tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/calendar/agenda_js.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the forums tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the dropbox tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/dropbox/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the users tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/user/user.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the groups tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/group/group.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error




  Scenario: Make sure the chat tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/resources/chat/?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the assignments tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the surveys tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/survey/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the wiki tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/wiki/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the notebook tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/notebook/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the projects tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I click the "button.p-button-icon-only" element
    And I wait for the page to be loaded
    Then I follow "Blog"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the reporting tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/tracking/courseLog.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the settings tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/course_info/infocours.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the backup tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/course_info/maintenance.php?cid=3"
    And I wait for the page to be loaded
    Then I should not see an error

  # Ported from tests/behat/features/course.feature — rewritten, not
  # verbatim, to match a REAL behavior fix that landed alongside the Behat
  # rewrite (upstream e56f09bb221, "Course: Fix password-protected course
  # entry test"). This isn't just a selector-drift port: course entry is now
  # actually gated. Visiting a public course's modern homepage directly
  # redirects an unauthorized visitor to /main/auth/set_temp_password.php
  # (CidReqListener -> CourseAccessResolver::requiresRegistrationPassword())
  # whenever the course is public (visibility OPEN_WORLD, value 3 — "Public -
  # access allowed for the whole world") and has a non-empty registration
  # password, unless the visitor is an admin/teacher/already-subscribed/
  # session-coach. The PREVIOUS version of this scenario only toggled the
  # password field via the settings form and asserted no error — it never
  # actually exercised the gate at all. Uses a dedicated course
  # ("Password Protected" / code "PASSWORDPROTECTED") instead of reusing
  # "TEMP" so this genuinely password-gated course doesn't affect any other
  # scenario that enters TEMP (whose visitors here are always admin/
  # subscribed anyway, so TEMP itself was never actually a valid test of the
  # gate, only of the settings form saving without error).
  #
  # @skip 2026-08-06: recurring real-CI-only failure across two separate runs
  # ("Target page, context or browser has been closed" / "Password Protected"
  # text never found — a hard crash mid-scenario, not a logic error), never
  # reproducible locally despite the scenario passing cleanly in isolation and
  # in full-suite local runs multiple times when this was first written. Same
  # error signature already confirmed elsewhere in this suite as genuine CI-
  # runner/Chromium instability, not a test defect (see adminChamiloOrgBlock.
  # feature's and toolGroup.feature's own header comments for the same
  # confirmed-not-reproducible conclusion). Deferred rather than re-chased a
  # third time; revisit if a future CI run points to something more specific.
  # RE-ENABLED 2026-08-22. The @skip note kept below is preserved as history,
  # but its premise no longer holds: every one of those deferrals attributed the
  # failure to "concurrent-worker-load" / "real-CI-only" flakiness whose
  # suspected source was specialCase1PlatformSettings.feature mutating ~100
  # global platform settings (notably cookie_warning, a fixed bottom-of-viewport
  # overlay that intercepts pointer events) and its @long-scenario tests
  # starving the shared worker pool. SpecialCase1 has since been moved OUT of the
  # parallel batch into its own sequential CI step (@specialcase1 tag, see
  # package.json + playwright.yml), which removes that interference at the
  # source. Direct evidence it was real: toolAssessments.feature's five
  # NON-skipped scenarios were failing in CI before that change and pass after
  # it. Re-enabled to be judged on real results instead of staying dark.
  Scenario: Enter to public password-protected course
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in the following:
      | title       | Password Protected |
      | visual_code | PASSWORDPROTECTED  |
    # Same mandatory-category trap as "Create a course before testing" above
    # on this box (course.course_creation_form_set_course_category_mandatory
    # is on here) — an unfilled Categories field silently re-renders the same
    # form with a validation error instead of creating anything.
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    And I check the "Public - access allowed for the whole world" radio button
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Password Protected"
    And I resolve the numeric id of course "PASSWORDPROTECTED"

    Given I am on the course settings page of course "PASSWORDPROTECTED"
    And I wait for the page to be loaded
    And I click the "a[data-target='#collapse_course_access']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | course_registration_password | 123456 |
    And I press "Save settings"
    Then I wait for the page to be loaded
    Then I should not see an error

    # As a plain visitor (not admin/teacher/subscribed), the password gate
    # must actually be enforced.
    Given I am not logged
    And I am a student
    And I am on the modern homepage of course "PASSWORDPROTECTED"
    Then I should see "This course requires a password"
    When I fill in "course_password" with "wrong-password"
    And I press "Accept"
    And I wait for the page to be loaded
    Then I should see "The course password is incorrect"
    When I fill in "course_password" with "123456"
    And I press "Accept"
    And I wait for the page to be loaded when ready
    Then I should be on the modern homepage of course "PASSWORDPROTECTED"
    And I should see "Password Protected"
    And I should not see "The course password is incorrect"

  Scenario: Create a private course before testing
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"
    When I fill in "title" with "TEMP_PRIVATE"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    # Same fix as "Create a course before testing" above (this scenario predates
    # that fix and was never updated to match): an unselected Language field
    # silently creates TEMP_PRIVATE as a French-language course, which then
    # breaks any later feature relying on its English tool/message text —
    # confirmed by sessionAccess.feature's access-denied check rendering as
    # "Vous n'êtes pas autorisé dans ce cours" instead of "not allowed".
    And I select "English" from "course_language"
    Then I check the "Private access (access authorized to group members only)" radio button
    And I press "submit"
    Then wait for the page to be loaded
    Then I should not see an error
