# features/createUser.feature
@common
Feature: User login
  In order to log in
  As a registered user
  I need to be able to enter my details in the form and get in

  Scenario: Login as admin user successfully
    Given I am on "/index.php"
    When I fill in "login" with "admin"
    And I fill in "password" with "admin"
    And I press "submitAuth"
    Then I should see "John Doe"
    And I should see "Administration"