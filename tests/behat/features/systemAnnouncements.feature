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
    And I fill in ckeditor field "content" with "Announcement system description"
    And I press "Add news"
    Then I should see "Announcement has been added"

  Scenario: Delete system announcement
    Given I am on "/main/admin/system_announcements.php"
    When I follow "Delete"
    And I confirm the popup
    Then I should see "Announcement has been deleted"
