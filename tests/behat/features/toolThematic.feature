Feature: Thematic tool

  Background:
    Given I am a platform administrator

  Scenario: Create
    Given I am on "/main/course_progress/index.php?cid=1&action=thematic_add"
    Then I fill in the following:
      | title | Thematic 1 |
    Then I fill in editor field "content" with "Description for thematic"
    And I press "Save"
    Then I should see "Thematic 1"

  Scenario: Read and add Thematic plan
    Given I am on "/main/course_progress/index.php?cid=1"
    Then I should see "Thematic 1"
    Then I follow "Edit thematic section"
    Then I should see "Title"
    Then I fill in the following:
      | title[1] | Objective |
    Then I fill in editor field "description[1]" with "Objective 1"
    Then I press "Save"
    Then I should see "Objective 1"

  Scenario: Update
    Given I am on "/main/course_progress/index.php?cid=1&action=thematic_edit&thematic_id=1"
    Then I should see "Edit thematic section"
    Then I fill in the following:
      | title | Thematic 1 edited |
    Then I fill in editor field "content" with "Description edited"
    Then I press "Save"
    Then I should see "Thematic 1 edited"

  Scenario: Delete
    Given I am on "/main/course_progress/index.php?cid=1&"
    Then I should see "Thematic 1 edited"
    Then I follow "Delete"
    Then I confirm the popup
    Then I should not see "Thematic 1 edited"
