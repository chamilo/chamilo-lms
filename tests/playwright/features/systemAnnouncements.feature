# Ported from tests/behat/features/systemAnnouncements.feature.
#
# - system_announcements.php is still the live legacy admin page.
# - Form: title, content (TinyMCE), roles multi-select (required; option
#   labels from api_get_roles(), e.g. "Invitee"), date range defaults to
#   now → +7 days so we don't fill it. Button label is "Add news"
#   (addButtonSend), not a bare submit name.
# - Roles multi-select is rendered as name="roles[]" (QuickForm appends [] for
#   multiple). Target "roles[]" explicitly — same as createUser's "roles[]".
# - Background visits course TEMP homepage (by code via cidReq) — needs the
#   "Seed test course" CI step (or a local TEMP course). Not used by the
#   announcement form itself; kept for parity with the original suite.
# - Delete scopes the icon to the row for our title so other portal news on
#   a dirty box are not removed.
Feature: System Announcements
  In order to use the System Announcements tool
  The admin should create system Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a system announcement
    Given I am on "/main/admin/system_announcements.php?action=add"
    And wait for the page to be loaded
    When I fill in the following:
      | title   | Announcement system test                       |
    And I fill in editor field "content" with "Announcement system description"
    And I select "Invitee" from "roles[]"
    And I press "Add news"
    And wait very long for the page to be loaded
    Then I should see "Announcement system test"
    And I should not see an error

  Scenario: Delete system announcement
    Given I am on "/main/admin/system_announcements.php"
    And I wait for the page to be loaded
    When I click the "i.mdi-delete" icon in the row for "Announcement system test"
    Then I confirm the popup
    And wait for the page to be loaded
    Then I should not see "Announcement system test"
    And I should not see an error
