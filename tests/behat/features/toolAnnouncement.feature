Feature: Announcement tool
  In order to use the Announcement tool
  The teachers should be able to create Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an announcement for admin user
    Given I am on "/main/announcements/announcements.php?action=add&cidReq=TEMP"
    When I fill in the following:
      | title   | Announcement test                       |
    And I press "choose_recipients"
    And I select "John Doe" from "users-f"
    And I press "add"
    And I fill in ckeditor field "content" with "Announcement description"
    And I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Create an announcement for all users
    Given I am on "/main/announcements/announcements.php?action=add&cidReq=TEMP"
    When I fill in the following:
      | title   | Announcement test                       |
    And I fill in ckeditor field "content" with "Announcement description"
    And I press "submit"
    Then I should see "Announcement has been added"

  Scenario: Delete all announcements
    Given I am on "/main/announcements/announcements.php?cidReq=TEMP"
    When I follow "Clear list of announcements"
    And I confirm the popup
    Then I should see "All announcements have been deleted"
