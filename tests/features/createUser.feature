@administration
Feature: Users management as admin
  In order to add users
  As an administrator
  I need to be able to create new users

  Scenario: Create a user with only basic info
    Given I am a platform administrator
    And I am on "/main/admin/user_add.php"
    When I fill in "firstname" with "Sammy"
    And I fill in "lastname" with "Marshall"
    And I fill in "username" with "smarshall"
    And I fill in "email" with "smarshall@example.com"
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: Search and delete a user
    Given I am a platform administrator
    And I am on "/main/admin/user_list.php"
    And I fill in "user-search-keyword" with "smarshall"
    And I press "submit"
    When I follow "Delete"
    Then I should see "The user has been deleted"
