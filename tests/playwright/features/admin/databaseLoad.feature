# Database load panel on System Status — MySQL/MariaDB SHOW GLOBAL STATUS metrics.
# UI: assets/vue/views/admin/SystemStatus.vue (database section collapsible panel)
# Data: SystemStatusController::databaseData() at /admin/system-status-database-data
# Access: class-level #[IsGranted('ROLE_ADMIN')]
#
# Same access model as webserverLoad.feature: Vue-router deny on the SPA
# shell, not a Symfony 403. See that file's header for why there is no
# Feature Background and why the deny scenario asserts the pathname.
Feature: Database load metrics on system status
  In order to monitor whether the database server is under strain
  As a global administrator
  I want to inspect mytop-style load metrics on the Database section
  But non-administrators must not see those metrics

  Scenario: Administrator sees database identity rows
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=database"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Database load"
    And I should see "driver"

  Scenario: Database load panel is collapsed by default
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=database"
    And I wait for the page to be loaded
    Then I should see "Database load"
    And I should not see "Auto-refresh every 5 seconds"

  Scenario: Expanding the panel reveals load metrics
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/system-status?section=database"
    And I wait for the page to be loaded
    When I click the "#database-load-toggle" element
    And I wait for the page to be loaded
    Then I should see "Auto-refresh every 5 seconds"
    And I should see "Uptime"
    And I should see "Slow queries"
    And I should see "Threads connected"

  Scenario: Non-administrators cannot access the database load page
    Given I am not logged
    And I am a student
    And I wait for the page to be loaded
    When I am on "/admin/system-status?section=database"
    And I wait for the page to be loaded
    Then the page path should not start with "/admin/system-status"
    And I should not see the "#database-load-toggle" element
    And I should not see "Database load"
