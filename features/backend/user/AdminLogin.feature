@user
Feature: Admin dashboard protection
  In order to protect admin
  As a user
  I need to forbid access to admin from basic user

  Scenario: Try to access admin with simple account
    Given I am logged in as Student
    When I fill in "_username" with "<username>"
    And I fill in "_password" with "<password>"
    And I press "_submit"
    Then I should see "<message>"
