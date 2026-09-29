# Ported from tests/behat/features/toolAttendance.feature. The legacy page
# (public/main/attendance/index.php, public/main/inc/lib/attendance.lib.php)
# is still live and its form fields/strings are unchanged.
#
# - Fixed the "wait THE page to be loaded when ready" typo (missing "for") —
#   same typo already found and fixed once in course.feature (never matched
#   any real Behat step in the original suite either, since FeatureContext
#   only registers "wait FOR the page...").
# - `attendance_id=1` is NOT a safe hardcoded assumption here, unlike course
#   ids: `CAttendance::$iid` is a single GLOBAL AUTO_INCREMENT on
#   `c_attendance`, shared across every course on the platform (not scoped
#   per-course the way a fresh course's own id is), so a fresh install's
#   first-ever attendance is not guaranteed to land on id=1. Capturing the
#   real id from attendance_add's own success redirect instead (which
#   appends a real `attendance_id=<id>` query param) via the new "I remember
#   the created attendance id" / "I am on the attendance page ..." steps —
#   same pattern already used for socialGroup's created-group id.
# - The Delete link is icon-only (no visible text/accessible name — its
#   `title="Delete"` attribute sits on a nested <i>, not the <a> itself), so
#   "I follow 'Delete'" is unreliable here; used the existing generic
#   "I click the ... element" step with an href-substring CSS selector
#   instead, which doesn't depend on accessible-name computation.
Feature: Attendance tool

  Background:
    Given I am a platform administrator

  Scenario: Create
    Given I am on "/main/attendance/index.php?cid=3&action=attendance_add"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | Attendance 1 |
    Then I fill in editor field "description" with "Description for attendance"
    Then wait for the page to be loaded
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Add a date time"
    And I remember the created attendance id

  Scenario: Read
    Given I am on "/main/attendance/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should see "Attendance 1"
    Then I follow "Attendance 1"
    And I wait for the page to be loaded
    Then I should see "The attendance sheets allow you to specify a list of dates"

  Scenario: Update
    Given I am on the attendance page "/main/attendance/index.php?cid=3&action=attendance_edit&attendance_id=ATTENDANCE_ID"
    And I wait for the page to be loaded
    Then I should see "Edit"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Attendance 1 edited |
    Then I fill in editor field "description" with "Description edited"
    Then I press "Update"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Delete
    Given I am on "/main/attendance/index.php?cid=3&sid=0"
    And I wait for the page to be loaded
    Then I should see "Attendance 1 edited"
    Then I click the "a[href*='attendance_delete']" element
    And I wait for the page to be loaded
    Then I should not see "Attendance 1 edited"
