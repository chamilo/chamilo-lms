@administration
Feature: Users management as admin
  In order to add users
  As an administrator
  I need to be able to create new users

  Scenario: See the users list link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/index.php"
    Then I should see "Users list"

  Scenario: See the user creation link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/index.php"
    Then I should see "Add a user"

  Scenario: Create a user with only basic info
    Given I am a platform administrator
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | Sammy                 |
      | lastname  | Marshall              |
      | email     | smarshall@example.com |
      | username  | smarshall             |
      | password  | smarshall             |
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: Search and delete a user
    Given I am a platform administrator
    And Admin top bar is disabled
    And I am on "/main/admin/user_list.php"
    And I fill in "keyword" with "smarshall"
    And I press "submit"
    When I follow "Delete"
    And I confirm the popup
    Then I should see "The user has been deleted"
