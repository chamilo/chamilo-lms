Feature: Admin System block navigation
  In order to verify admin system-related pages
  As a platform administrator
  I want to open system admin pages and ensure they load without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Clean temporary files
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Clean temporary files"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Special exports
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Special exports"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open System status
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "System status"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Data filler
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Data filler"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open E-mail tester
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "E-mail tester"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Tickets
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Tickets"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Update session status
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Update session status"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Colors
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Colors"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open File info
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "File info"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Resources by type
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Resources by type"
    And I wait for the page to be loaded
    Then I should not see an error
