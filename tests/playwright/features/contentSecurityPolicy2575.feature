@csp2575
Feature: Optional Content Security Policy
  The browser verifies the explicitly configured CSP mode on an isolated test portal.
  Set CSP2575_MODE to disabled, report_only or enforce before running this feature.

  Scenario: The login shell uses the configured CSP and its trusted scripts
    When I verify the configured CSP2575 mode on the login shell
    Then the CSP2575 browser probe must match the configured mode
