Feature: Announcement tool
  In order to use the Announcement tool
  The teachers should be able to create Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an announcement for admin user
    Given I am on "/main/announcements/announcements.php?action=add&cid=1"
    When I fill in the following:
      | title   | Announcement test                       |
    And I press "choose_recipients"
    And I select "John Doe" from "users"
    And I press "add"
    And I fill in editor field "content" with "Announcement description"
    And I follow "Preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And wait for the page to be loaded
    Then I should see "Announcement has been added"
# this is not finalising at the moment so we can not test the rest of this scenario
# I create it but without testing so it need to be verified before uncommenting
#    Then I follow "inbox"
#    Then I should see "Inbox"
#    And I should see "Your received a annoucement \"Announcement test\""


  Scenario: Create an announcement for all users
    Given I am on "/main/announcements/announcements.php?action=add&cid=1"
    When I fill in the following:
      | title   | Announcement test                       |
    And I fill in editor field "content" with "Announcement description"
    And I follow "Preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    Then I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Announcement has been added"

#  Scenario: Delete all announcements
#    Given I am on "/main/announcements/announcements.php?cid=1"
#    When I follow "Clear list of announcements"
#    And I confirm the popup
#    Then I should see "All announcements have been deleted"
