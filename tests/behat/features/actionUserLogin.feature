Feature: Login user

  Scenario: Login as admin user successfully
    Given I am on "/login"
    Then I should see "Sign in"
    And I fill in "admin" for "login"
    And I fill in "admin" for "password"
    Then I press "Sign in"
    Then wait very long for the page to be loaded
    #Then I should see "MyCourses"
    Then I should not see an error

  Scenario: Login as admin
    Given I am a platform administrator
    Then I should not see an error
    #Then I should see "MyCourses"