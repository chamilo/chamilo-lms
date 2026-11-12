@common @admin
Feature: Configurable maximum time per learning session
  In order to keep connection totals realistic
  As a platform administrator
  I want to see the maximum counted session duration setting

  Scenario: The tracking settings include the configurable session duration limit
    Given I am a platform administrator
    And I am on "/admin/settings/tracking"
    And wait for the page to be loaded
    Then I should see "Max time spent per session threshold (minutes)"
    And I should not see an error
