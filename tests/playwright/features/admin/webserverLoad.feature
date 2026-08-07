# Web server load panel on System Status — Apache mod_status / Nginx stub_status.
# UI: assets/vue/views/admin/SystemStatus.vue (webserver section collapsible panel)
# Data: SystemStatusController::webserverData() at /admin/system-status-webserver-data
# Access: class-level #[IsGranted('ROLE_ADMIN')]
#
# Metrics only appear when a localhost status path responds. The panel always
# explains which paths are scanned and that a status module is required.
#
# Non-admin assertion uses content absence, not HTTP 403: prod-mode ExceptionListener
# converts AccessDeniedHttpException to a 302 redirect (final page status 200).
Feature: Web server load metrics on system status
  In order to estimate current Apache or Nginx load over time
  As a global administrator
  I want to inspect timestamped status metrics on the Web server section
  But non-administrators must not see those metrics

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Administrator sees the web server load panel
    Given I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Web server load"

  Scenario: Web server load panel is collapsed by default
    Given I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then I should see "Web server load"
    And I should not see "Auto-refresh every 5 seconds"

  Scenario: Expanding the panel shows status module guidance
    Given I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    When I click the "#webserver-load-toggle" element
    And I wait for the page to be loaded
    Then I should see "Auto-refresh every 5 seconds"
    And I should see "Requires a local web server status module"
    And I should see "Paths scanned"

  Scenario: Non-administrators cannot access the web server load page
    Given I am a student
    And I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then the response status code should be 200
    And I should not see "Web server load"
