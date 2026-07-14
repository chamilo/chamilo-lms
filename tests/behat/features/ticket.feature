Feature: Ticket
  In order to manage support requests
  Users should be able to use the Ticket Vue interface according to their permissions

  Background:
    Given I am a platform administrator

  Scenario: Open the Ticket list
    Given I am on "/tickets?project_id=1"
    And I wait for the page to be loaded
    Then I should see "My tickets"
    And I should not see an error

  Scenario: Create a ticket
    Given I am on "/tickets/create?project_id=1"
    And I wait for the page to be loaded
    When I fill in the following:
      | subject | Vue functional ticket |
    And I fill in tinymce field "ticket-content" with "Ticket description from the Vue interface"
    And I press "Send message"
    Then I wait very long for the page to be loaded
    And I should see "Vue functional ticket"
    And I should not see an error

  Scenario: Check Ticket projects
    Given I am on "/tickets/settings?section=projects"
    And I wait for the page to be loaded
    Then I should see "Ticket System"
    And I should not see an error

  Scenario: Check Ticket categories
    Given I am on "/tickets/settings?section=categories&project_id=1"
    And I wait for the page to be loaded
    Then I should see "Enrollment"
    And I should not see an error

  Scenario: Check Ticket statuses
    Given I am on "/tickets/settings?section=statuses"
    And I wait for the page to be loaded
    Then I should see "New"
    And I should not see an error

  Scenario: Check Ticket priorities
    Given I am on "/tickets/settings?section=priorities"
    And I wait for the page to be loaded
    Then I should see "Normal"
    And I should not see an error

  Scenario: Create a Ticket project
    Given I am on "/tickets/settings?section=projects"
    And I wait for the page to be loaded
    When I click the "#ticket-settings-add" element
    And I fill in "title" with "Vue Ticket Project"
    And I fill in tinymce field "ticket-setting-description" with "Project created from Vue"
    And I click the "#ticket-settings-save" element
    Then I wait very long for the page to be loaded
    And I should see "Vue Ticket Project"
    And I should not see an error

  Scenario: Create a Ticket category
    Given I am on "/tickets/settings?section=categories&project_id=1"
    And I wait for the page to be loaded
    When I click the "#ticket-settings-add" element
    And I fill in "title" with "Vue Ticket Category"
    And I fill in tinymce field "ticket-setting-description" with "Category created from Vue"
    And I click the "#ticket-settings-save" element
    Then I wait very long for the page to be loaded
    And I should see "Vue Ticket Category"
    And I should not see an error

  Scenario: Create a Ticket status
    Given I am on "/tickets/settings?section=statuses"
    And I wait for the page to be loaded
    When I click the "#ticket-settings-add" element
    And I fill in "title" with "Vue Ticket Status"
    And I fill in tinymce field "ticket-setting-description" with "Status created from Vue"
    And I click the "#ticket-settings-save" element
    Then I wait very long for the page to be loaded
    And I should see "Vue Ticket Status"
    And I should not see an error

  Scenario: Create a Ticket priority
    Given I am on "/tickets/settings?section=priorities"
    And I wait for the page to be loaded
    When I click the "#ticket-settings-add" element
    And I fill in "title" with "Vue Ticket Priority"
    And I fill in tinymce field "ticket-setting-description" with "Priority created from Vue"
    And I click the "#ticket-settings-save" element
    Then I wait very long for the page to be loaded
    And I should see "Vue Ticket Priority"
    And I should not see an error

  Scenario: Deny Ticket settings to a student
    Given I am a student
    When I am on "/tickets/settings?section=projects"
    And I wait for the page to be loaded
    Then I should not see "Vue Ticket Project"
