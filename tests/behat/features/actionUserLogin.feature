Feature: Login user

  Scenario: Login as admin user successfully
    Given I am on "/login"
    And wait for the page to be loaded when ready
    Then I should see "Sign in"
    When I fill in "admin" for "login"
    And I fill in "admin" for "password"
    And I press "Sign in"
    And wait for the page to be loaded when ready
    #Then I should see "MyCourses"
    Then I should not see an error

  Scenario: Login as admin
    Given I am a platform administrator
    And wait for the page to be loaded when ready
    Then I should not see an error
