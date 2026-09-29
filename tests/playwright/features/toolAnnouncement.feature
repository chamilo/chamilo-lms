# Ported from tests/behat/features/toolAnnouncement.feature. The legacy page
# (public/main/announcements/announcements.php) is still fully live (a
# parallel Vue SPA also exists at /resources/announcement/:nodeId, but only
# the course-tool link routes there — direct URL navigation, as these
# scenarios do, still hits the legacy page unchanged).
#
# Confirmed all selectors against a real running instance before porting
# (not just static source reading):
# - "users" is still the dual-listbox widget (CourseManager::
#   addUserGroupMultiSelect() -> FormValidator::addMultiSelect(), same widget
#   already handled for socialGroup.feature's invitation field) — left
#   `#users`, right `#users_to`. Selecting "John Doe" by name (rather than a
#   numeric id, which would be unstable across installs) uses the new
#   generalized "I select ... from the multiselect ..." step.
# - The submit button only becomes visible after "Preview" is clicked
#   (announcements.php's own JS: `$('#send_button').show()` inside the
#   preview click handler) — both scenarios below already follow "Preview"
#   before pressing "submit", matching that real requirement.
# - The delete flow's jqGrid confirm dialog renders its "Delete"/"Cancel"
#   buttons as `<a role="button" class="fm-button">`, not real <button>
#   elements — confirmed via live DOM inspection. Needed a fix to the
#   shared pressButton() helper (see common.steps.ts) to actually resolve
#   these; without it "I press 'Delete'" here would fail.
# - UPDATE — a real CI run showed the original "select all" approach (a
#   blind click on the `th.ui-th-ltr` header cell, relying on jqGrid's own
#   click delegation to toggle the checkbox inside it) occasionally not
#   actually selecting any row (cold jqGrid bootstrap timing) — the delete
#   flow then hit a "Please, select row" warning dialog instead of the real
#   confirmation, and "I press 'Delete'" fell through to an unrelated
#   per-row delete icon hidden behind that warning's backdrop, hanging for
#   the full test timeout. Replaced with a new, verified
#   "I select all rows in the ... grid" step that clicks the real checkbox
#   directly and asserts it's actually checked before moving on.
Feature: Announcement tool
  In order to use the Announcement tool
  The teachers should be able to create Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an announcement for admin user
    Given I am on "/main/announcements/announcements.php?action=add&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | title   | Announcement test                       |
    And I press "choose_recipients"
    And I select "John Doe" from the multiselect "users"
    And I fill in editor field "content" with "Announcement description"
    And I follow "Preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    And I should see "John Doe"
    Then I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement for all users
    Given I am on "/main/announcements/announcements.php?action=add&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | title   | Announcement test                       |
    And I fill in editor field "content" with "Announcement description"
    And I follow "Preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Delete all announcements
    Given I am on "/main/announcements/announcements.php?cid=3"
    And I wait for the page to be loaded
    Then I select all rows in the "announcements" grid
    And I click the "span.mdi-trash-can-outline" element
    And I press "Delete"
    And I wait for the page to be loaded
    Then I should not see "Announcement test"
