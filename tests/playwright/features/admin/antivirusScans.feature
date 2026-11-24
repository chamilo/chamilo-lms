@common @tools
Feature: Antivirus scan report
  Audit history is read-only and restricted to platform administrators.

  Scenario: An administrator can access the antivirus report
    Given I am a platform administrator
    And I am on "/admin/antivirus-scans"
    And I wait for the page content to settle
    Then I should see the "#antivirus-status" element
    And I should see the "a[href='/admin/antivirus-scans-export?status=']" element
    When I click the "#antivirus-status" element
    And I click the ".p-select-overlay [role='option']:nth-child(3)" element
    Then I should see the "a[href='/admin/antivirus-scans-export?status=infected']" element
    When I click the "#antivirus-status" element
    And I click the ".p-select-overlay [role='option']:nth-child(1)" element
    Then I should see the "a[href='/admin/antivirus-scans-export?status=']" element
    And I should not see an error

  Scenario: A student cannot access the antivirus report
    Given I am a student
    And I am on "/admin/antivirus-scans"
    And I wait for the page content to settle
    Then I should not see the "#antivirus-status" element
    And I should not see the "a[href^='/admin/antivirus-scans-export']" element
    # A JSON request, as the SPA makes it: a plain navigation is redirected to the home page in production.
    Then a JSON request to "/admin/antivirus-scans-data" should be answered with status 403
    And a JSON request to "/admin/antivirus-scans-export" should be answered with status 403
