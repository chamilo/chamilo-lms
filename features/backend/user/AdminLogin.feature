@user
Feature: Admin dashboard protection
  In order to protect admin
  As a user
  I need to forbid access to admin from basic user

  @javascript
  Scenario: Try to access admin with simple account
    Given I am logged in student
    When I go to the admin_dashboard page
    Then I should see "Access Denied"
#    And the response status code should be 403

  @javascript
  Scenario: Try to access admin with admin account
    Given I am logged in as administrator
    When I go to the admin_dashboard page
    Then I should see "Welcome"
