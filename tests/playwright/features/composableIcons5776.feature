@common @tools
Feature: Composable action icons
  Resource-specific actions use modifiers without changing their labels or destinations.

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: A composed document button retains its icon and action
    When I follow "Documents"
    Then I should see the "button[title='New folder'] .base-icon--compact-badge > i.mdi-folder" element
    And I should see the "button[title='New folder'] .base-icon__badge .mdi-star" element
    And I should see the "button[title='New document'] .mdi-file-plus" element
    And I should not see the "button[title='New document'] .base-icon__badge" element
    When I press "New folder"
    Then I should see the ".p-dialog input[name='title']" element

  Scenario: A composed exercise link retains its icon and destination
    When I follow "Tests"
    Then I should see the "a[title='Create exercise'] .base-icon--compact-badge > i.mdi-checkbox-multiple-marked-outline" element
    And I should see the "a[title='Create exercise'] .base-icon__badge .mdi-star" element
    And I should see the "a[title='Import Aiken quiz'] .base-icon__badge .mdi-arrow-up" element
    When I follow "Create exercise"
    Then I should see "Create a new test"
