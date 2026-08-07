Feature: System status diagnostics
  In order to inspect platform health from the administration area
  As a platform administrator
  I want to open the system status page, switch diagnostic sections, and load their data
  But non-administrators must not access it

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Administrator opens system status from the SPA route
    Given I am on "/admin/system-status"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "System status"
    And I should see "Chamilo"
    And I should see "PHP"
    And I should see "Database"
    And I should see "Web server"
    And I should see "Paths"
    And I should see "Courses space"

  Scenario: Administrator can open the PHP section via query parameter
    Given I am on "/admin/system-status?section=php"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "System status"
    And I should see "phpversion()"
    And I should see "PHP cache"
    And I should not see "Auto-refresh every 5 seconds"

  Scenario: Administrator can expand the PHP cache panel
    Given I am on "/admin/system-status?section=php"
    And I wait for the page to be loaded
    When I press "php-cache-toggle"
    And I wait for the page to be loaded
    Then I should see "OPcache"
    And I should see "APCu"

  Scenario: Administrator can open courses space section
    Given I am on "/admin/system-status?section=courses_space"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Course code"

  Scenario: Legacy URL redirects to the Vue page preserving the section
    Given I am on "/main/admin/system_status.php?section=database"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "System status"
    And I should see "driver"

  Scenario: Non-administrators cannot load system status data
    Given I am a student
    And I am on "/admin/system-status-data?section=php"
    Then the response status code should be 403
