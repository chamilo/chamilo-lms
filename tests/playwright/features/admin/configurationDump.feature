Feature: Download a safe configuration report for support
  Platform administrators can export a read-only report without revealing secret values.

  Scenario: Administrator downloads the configuration report
    Given I am a platform administrator
    And I am on "/admin/settings/platform"
    Then I should see the "#configuration-dump-download" element
    When I download the file from the link "Download configuration for debugging"
    Then the downloaded file should contain "chamilo-configuration-dump"
    And the downloaded file should contain "[REDACTED]"
    And the downloaded file should contain "current_access_url_only"

  Scenario: The download is absent for students
    Given I am a student
    And I am on "/admin/settings/platform"
    Then I should not see the "#configuration-dump-download" element
