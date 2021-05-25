Feature: Classes
  In order to use the Classes
  As an administrator
  I need to be able to create a class

  Scenario: Create a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php?action=add"
    When I fill in the following:
      | name | Class 1 |
    Then I fill in editor field "description" with "description"
    Then I attach the file "/public/img/logo.png" to "picture"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Item added"

  Scenario: Update a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php"
    And wait for the page to be loaded
    Then I should see "Class 1"
    Then I follow "Edit"
    When I fill in the following:
      | name | Class 1 Edited |
    Then I fill in editor field "description" with "description"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Update successful"

  Scenario: Delete a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php"
    And wait for the page to be loaded
    Then I should see "Class 1"
    Then I follow "Delete"
    And confirm the popup
    Then I should not see "Class 1"
