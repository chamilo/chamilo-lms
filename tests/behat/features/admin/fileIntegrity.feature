Feature: File integrity monitoring
  In order to detect unexpected changes to the installed files
  As a global administrator
  I want to review the file integrity report and control scanning
  But as a security boundary, pausing or rebaselining must require my password again

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Global administrator sees the report and the admin actions
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "File integrity"
    And I should see "Actions"

  Scenario: Global administrator can run a scan on demand
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    And I press "Run a scan now"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Last scan"

  Scenario: Pausing alerting is refused with a wrong password
    Given I am on "/admin/security/file-integrity"
    And I wait for the page to be loaded
    And I fill in "file-integrity-pause-password" with "not-the-right-password"
    And I press "Pause for 1 hour"
    And I wait for the page to be loaded
    Then I should not see "Alerting is currently paused for maintenance."

  Scenario: Non-administrators cannot access the file integrity page
    Given I am a student
    And I am on "/admin/security/file-integrity"
    Then the response status code should be 403
