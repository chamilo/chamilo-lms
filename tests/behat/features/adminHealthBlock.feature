Feature: Admin Health check block
  In order to verify admin health checks
  As a platform administrator
  I want to open the health check page and ensure it loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Health check
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Health check"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: See health warnings
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Health check"
    And I wait for the page to be loaded
    Then I should not see an error
