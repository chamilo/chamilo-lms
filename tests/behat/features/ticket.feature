Feature: Ticket
  In order to use the ticket tool
  The admin should be able to create a ticket

  Background:
    Given I am a platform administrator

  Scenario: Check ticket categories
    Given I am on "/main/ticket/categories.php?project_id=1"
    Then I should see "Enrollment"

  Scenario: Check ticket projects
    Given I am on "/main/ticket/projects.php?project_id=1"
    Then I should see "Ticket System"

  Scenario: Check ticket status
    Given I am on "/main/ticket/status.php"
    Then I should see "New"

  Scenario: Check ticket priorities
    Given I am on "/main/ticket/priorities.php"
    Then I should see "Normal"

  Scenario: Create a ticket
    Given I am on "/main/ticket/new_ticket.php?project_id=1"
    Then I should see "Compose message"
    When I fill in the following:
      | subject | First ticket |
    And I fill in ckeditor field "content" with "Ticket description"
    And I fill in select bootstrap static input "#category_id" select "1"
    #category id = 1 => Enrollment: Tickets about enrollment
    And I press "Send message"
    Then I should see "created"
#
#  Scenario: Create ticket project
#    Given I am on "/main/ticket/projects.php?action=add"
#    When I fill in the following:
#      | name | Project 2 |
#    And I fill in ckeditor field "description" with "Project description"
#    And I press "Save"
#    Then I should see "Added"
#
#  Scenario: Create ticket status
#    Given I am on "/main/ticket/status.php?action=add"
#    When I fill in the following:
#      | name | Status 1 |
#    And I fill in ckeditor field "description" with "Status"
#    And I press "Save"
#    Then I should see "Added"
#
#  Scenario: Create priority
#    Given I am on "/main/ticket/priorities.php?action=add"
#    When I fill in the following:
#      | name | Priority 1 |
#    And I fill in ckeditor field "description" with "Priority"
#    And I press "Save"
#    Then I should see "Added"
