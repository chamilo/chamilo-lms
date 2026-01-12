Feature: Admin Skills block navigation
  In order to verify admin skills-related pages
  As a platform administrator
  I want to open skills admin pages and ensure they load without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Skills wheel
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Skills wheel"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Skills import
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Skills import"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Manage skills
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Manage skills"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Manage skills levels
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Manage skills levels"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Skills ranking
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Skills ranking"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Skills and assessments
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Skills and assessments"
    And I wait for the page to be loaded
    Then I should not see an error
