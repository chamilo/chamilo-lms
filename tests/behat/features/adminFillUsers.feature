Feature: Fill users

  Scenario: Login as admin user successfully
    Given I am on "/login"
    Then I should see "Sign in"
    And I fill in "admin" for "login"
    And I fill in "admin" for "password"
    Then I press "Login"
    Then I should not see an error

  Scenario: Create tests users successfully
    Given I am a platform administrator
    Then I am on "/main/admin/filler.php?fill=users"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Login as student user successfully
    Given I am a student
    Then I should not see an error

  Scenario: Login as teacher successfully
    Given I am a teacher
    Then I should not see an error

  Scenario: Login as HRD successfully
    Given I am an HR manager
    Then I should not see an error

  Scenario: Login as student boss successfully
    Given I am a student boss
    Then I should not see an error

  Scenario: Login as invitee successfully
    Given I am an invitee
    Then I should not see an error
