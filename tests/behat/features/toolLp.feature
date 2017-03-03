Feature: LP tool
  In order to use the LP tool
  The teachers should be able to create LPs

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a LP category
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=add_lp_category"
    When I fill in the following:
      | name | LP category 1 |
    And I press "submit"
    Then I should see "Added"

  Scenario: Create a LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=add_lp"
    When I fill in the following:
      | lp_name | LP 1 |
#    And I select "LP category 1" from "category_id"
    And I press "submit"
    Then I should see "Click on the [Learner view] button to see your learning path"

  Scenario: Add document to LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Edit learnpath"
    And I follow "Create a new document"
    When I fill in the following:
      | idTitle | Document 1 |
    And I fill in ckeditor field "content_lp" with "Sample HTML text"
    And I press "submit_button"
    Then I should see "Document 1"

  Scenario: Add an exercise to LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Edit learnpath"
    And I follow "Tests"
    And I follow "Exercise 1"
    Then I should see "Adding a test to the course"
    And I press "submit_button"
    Then I should see "Click on the [Learner view] button to see your learning path"
    And I should see "Exercise 1"

  Scenario: Enter LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "LP 1"
    And wait for the page to be loaded
    Then I should see "LP 1"
    And I should see "Document 1"
    And I should see "Exercise 1"

  Scenario: Delete a LP category
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Delete"
    Then I should not see "LP category 1"

  Scenario: Delete a LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Delete"
    And I confirm the popup
    Then I should not see "LP 1"

