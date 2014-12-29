@user
Feature: Admin dashboard protection
  In order to protect admin
  As a user
  I need to forbid access to admin from basic user

  Scenario: Try to access admin with simple account
    Given I am logged in student
    When I go to the administration page
    Then I should see "Access denied"

  Scenario: Try to access admin with admin account
    Given I am logged in as administrator
    When I go to the administration page
    Then I should see "Admin Dashboard"
