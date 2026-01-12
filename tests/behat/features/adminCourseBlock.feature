Feature: Admin Course management block
  In order to verify course management admin pages
  As a platform administrator
  I want to open each course management link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Course list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Course list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Add course
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Add course"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Export courses
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Export courses"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import courses list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Import courses list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Course categories
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Course categories"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Add a user to a course
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Add a user to a course"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import users list (course block)
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Import users list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Manage extra fields for courses
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Manage extra fields for courses"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Questions
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Questions"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Resources sequencing
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Resources sequencing"
    And I wait for the page to be loaded
    Then I should not see an error
