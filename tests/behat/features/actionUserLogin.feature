Feature: Login user

  Scenario: Login as admin user successfully
    Given I am on "/login"
    Then I should see "Sign in"
    And I fill in "admin" for "login"
    And I fill in "admin" for "password"
    Then I press "Login"
    Then wait for the page to be loaded
    Then I should not see "Login"
    Then I should not see an error

  Scenario: Login as admin
    Given I am a platform administrator
    Then I should not see an error
    Then I should not see "Login"