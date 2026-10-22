Feature: Confidential welfare reporting privacy
  # The server feature must be explicitly enabled with a valid secret and its V310 migration.
  # Do not use actual incidents, real victims, or personal evidence in browser tests.

  Scenario: Confidential reporting requires a direct authenticated session
    Given I am not logged
    When I am on "/welfare"
    Then I should not see "My confidential reports"

  Scenario: The reporter form is available to authenticated users when enabled
    Given I am a student
    When I am on "/welfare/new"
    And I wait for the page to be loaded
    Then I should see "New confidential report"

  # This scenario runs only where CHAMILO_WELFARE_ENABLED=1 and the encryption key is configured.
  Scenario: The confidential reporting screen is accessible to a directly logged-in student
    Given I am a student
    When I am on "/welfare"
    And I wait for the page to be loaded
    Then I should see "Confidential reporting"
    And I should see "New confidential report"
