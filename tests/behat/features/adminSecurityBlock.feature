Feature: Admin Security block navigation
  In order to verify admin security-related pages
  As a platform administrator
  I want to open security admin pages and ensure they load without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Login attempts
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Login attempts"
    And I wait for the page to be loaded
    Then I should not see an error
