# features/courseTools.feature#
# This test leaves course cid=1 titled "TEMP" available for other tests
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
    And I am on "/main/lp/lp_controller.php?action=list&cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the links tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/link/link.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the tests tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/exercise/exercise.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the announcements tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/announcements/announcements.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the assessments tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/gradebook/index.php?cid=1"
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
    And I am on "/main/glossary/index.php?cid=1"
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
    And I am on "/main/calendar/agenda_js.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the forums tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/forum/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the dropbox tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/dropbox/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the users tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/user/user.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the groups tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/group/group.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error




  Scenario: Make sure the chat tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/resources/chat/?cid=1"
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
    And I am on "/main/survey/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the wiki tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/wiki/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the notebook tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/notebook/index.php?cid=1"
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
    And I am on "/main/tracking/courseLog.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the settings tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/course_info/infocours.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error


  Scenario: Make sure the backup tool is available
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/course_info/maintenance.php?cid=1"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Enter to public password-protected course
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I click the "button.p-button-icon-only" element
    And I wait for the page to be loaded
    Then I follow "Course settings"
    And I wait for the page to be loaded
    # Original Behat selector targeted a CLASS ("collapse_course_access") on
    # this toggle; the current markup keeps the same id as a data-target
    # attribute instead (<a data-toggle="collapse" data-target="#collapse_
    # course_access">), no class of that name exists anymore. Single-quoted
    # inside the CSS selector since the whole thing sits inside a
    # double-quoted Gherkin string.
    And I click the "a[data-target='#collapse_course_access']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | course_registration_password | abc |
    # Original Behat button was id/name="submit"; the redesigned Course
    # settings form's save button is now name="submit_save" with visible
    # text "Save settings" — pressButton()'s id/name tier no longer matches
    # "submit" at all, so this falls through to its text-based fallback,
    # which needs the CURRENT visible label.
    And I press "Save settings"
    Then I wait for the page to be loaded
    Then I should not see an error

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
