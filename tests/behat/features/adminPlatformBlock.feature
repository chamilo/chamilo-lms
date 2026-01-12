Feature: Admin Platform management block
  In order to verify administration platform pages
  As a platform administrator
  I want to open each platform management link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Configuration settings
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Configuration settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Languages
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Languages"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Plugins
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Plugins"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Regions
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Regions"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Portal news
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Portal news"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Global agenda
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Global agenda"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import course events
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Import course events"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Pages
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Pages"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Setting the registration page
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Setting the registration page"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Statistics
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Statistics"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Reports
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Reports"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Teachers time report
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Teachers time report"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Extra fields
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Extra fields"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Configure multiple access URL
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Configure multiple access URL"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Mail templates
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Mail templates"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open External tools (LTI)
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "External tools (LTI)"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Contact form categories
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Contact form categories"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open System templates
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "System templates"
    And I wait for the page to be loaded
    Then I should not see an error
