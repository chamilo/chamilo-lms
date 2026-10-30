@common @tools
Feature: Global expired certificates report
  Only platform administrators can browse certificates expired across the current portal.

  Scenario: A platform administrator opens the global expired certificates report
    Given I am a platform administrator
    And I am on "/reporting/admin/expired-certificates"
    And I wait for the page content to settle
    Then I should see "Expired certificates"
    And I should not see an error

  Scenario: A student cannot access the global expired certificates report
    Given I am a student
    And I am on "/reporting/admin/expired-certificates"
    And I wait for the page content to settle
    Then I should not see "Expired certificates"
