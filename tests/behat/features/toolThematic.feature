Feature: Course progress tool

  Background:
    Given I am a platform administrator

  Scenario: Create a thematic section
    Given I am on "/main/course_progress/index.php?cid=1&action=thematic_add"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | Thematic 1 |
    And I fill in editor field "course_progress_thematic_content" with "Description for thematic"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Thematic 1"

  Scenario: Read and update the thematic plan
    Given I am on "/main/course_progress/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should see "Thematic 1"
    When I click the "a[title='Thematic plan']" element
    And I wait for the page to be loaded
    Then I should see "Thematic plan"
    And I fill in the following:
      | title[1] | Objective |
    And I fill in editor field "course_progress_plan_description_1" with "Objective 1"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Objective 1"

  Scenario: Update a thematic section
    Given I am on "/main/course_progress/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should see "Thematic 1"
    When I click the "a[title='Edit']" element
    And I wait for the page to be loaded
    Then I should see "Edit thematic section"
    And I fill in the following:
      | title | Thematic 1 edited |
    And I fill in editor field "course_progress_thematic_content" with "Description edited"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Thematic 1 edited"

  Scenario: Delete a thematic section
    Given I am on "/main/course_progress/index.php?cid=1"
    And I wait for the page to be loaded
    Then I should see "Thematic 1 edited"
    When I click the "button[aria-label='Delete']" element
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see "Thematic 1 edited"
