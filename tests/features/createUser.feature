@administration
Feature: User creation as admin
  In order to add users
  As an administrator
  I need to be able to create new users

Scenario: Create a user with only user's e-mail
  Given I am logged in
  And I am an administrator
  When I create a user with e-mail "sam@example.com"
  Then the user should be added
