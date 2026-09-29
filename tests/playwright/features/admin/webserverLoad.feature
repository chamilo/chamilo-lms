# Web server load panel on System Status — Apache mod_status / Nginx stub_status.
# UI: assets/vue/views/admin/SystemStatus.vue (webserver section collapsible panel)
# Data: SystemStatusController::webserverData() at /admin/system-status-webserver-data
# Access: class-level #[IsGranted('ROLE_ADMIN')]
#
# Metrics only appear when a localhost status path responds. The panel always
# explains which paths are scanned and that a status module is required.
#
# Non-admin access is a Vue-router deny (AdminSystemStatus meta.requiresAdmin),
# not a Symfony 403: IndexController serves the SPA shell at /admin/{vueRouting}
# with no IsGranted, so page.goto() is always a final 200. The client guard then
# replace-navigates to Home. A status-code assertion therefore cannot observe
# "denied", and "I should not see 'Web server load'" alone is racy if the
# previous Background login as admin is still in the same browser context —
# the panel can still be on screen while the student login is settling.
# No Feature Background: the deny scenario must start logged out, not as admin.
Feature: Web server load metrics on system status
  In order to estimate current Apache or Nginx load over time
  As a global administrator
  I want to inspect timestamped status metrics on the Web server section
  But non-administrators must not see those metrics

  Scenario: Administrator sees the web server load panel
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Web server load"

  Scenario: Web server load panel is collapsed by default
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then I should see "Web server load"
    And I should not see "Auto-refresh every 5 seconds"

  Scenario: Expanding the panel shows status module guidance
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    When I click the "#webserver-load-toggle" element
    And I wait for the page to be loaded
    Then I should see "Auto-refresh every 5 seconds"
    And I should see "Requires a local web server status module"
    And I should see "Paths scanned"

  Scenario: Non-administrators cannot access the web server load page
    Given I am not logged
    And I am a student
    And I wait for the page to be loaded
    When I am on "/admin/system-status?section=webserver"
    And I wait for the page to be loaded
    Then the page path should not start with "/admin/system-status"
    And I should not see the "#webserver-load-toggle" element
    And I should not see "Web server load"
