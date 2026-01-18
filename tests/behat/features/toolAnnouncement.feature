Feature: Announcement tool
  In order to use the Announcement tool
  The teachers should be able to create Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an announcement for admin user
    Given I am on "/main/announcements/announcements.php?action=add&cid=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | title   | Announcement test                       |
    And I press "choose_recipients"
    And I select "John Doe" from "users"
    And I press "add"
    And I fill in editor field "content" with "Announcement description"
    And I follow "Preview"
    And wait for the page to be loaded
    Then I should see "Announcement will be sent to"
    And I should see "John Doe"
    Then I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Create an announcement for all users
    Given I am on "/main/announcements/announcements.php?action=add&cid=1"
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
    Given I am on "/main/announcements/announcements.php?cid=1"
    And I wait for the page to be loaded
    Then I click the "th.ui-th-ltr" element
    And I click the "span.mdi-trash-can-outline" element
    And I press "Delete"
    And I wait for the page to be loaded
    Then I should not see "Announcement test"
