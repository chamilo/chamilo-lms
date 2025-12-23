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
    And I select "Invitee" from "Roles"
    And I press "Add news"
    And wait very long for the page to be loaded
    Then I should see "Announcement system test"
    And I should not see an error

  Scenario: Delete system announcement
    Given I am on "/main/admin/system_announcements.php"
    And I wait for the page to be loaded
    When I click the "i.mdi-delete" element
    Then I confirm the popup
    And wait for the page to be loaded
    Then I should not see "Announcement system test"
    And I should not see an error
