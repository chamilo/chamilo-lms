@common @tools
Feature: Password strength account disabling
  Only platform administrators can access the weak-password remediation screen.

  Scenario: An administrator can access the password strength remediation screen
    Given I am a platform administrator
    And I am on "/admin/security/password-strength"
    And I wait for the page content to settle
    Then I should see "Password strength checker"
    And I should see "Run password strength scan"
    And I should not see an error

  Scenario: A student cannot access the password strength remediation screen
    Given I am a student
    And I am on "/admin/security/password-strength"
    And I wait for the page content to settle
    Then I should not see "Run password strength scan"
