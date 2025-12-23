Feature: Classes
  In order to use the Classes
  As an administrator
  I need to be able to create a class

  Scenario: Create a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php?action=add"
    When I fill in the following:
      | title | Class 1 |
    Then I fill in editor field "description" with "description"
    Then I attach the file "/public/img/logo.png" to "picture"
    And I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Update a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php"
    And wait very long for the page to be loaded
    Then I should see "Class 1"
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Class 1 Edited |
    Then I fill in editor field "description" with "description"
    And I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Delete a class
    Given I am a platform administrator
    And I am on "/main/admin/usergroups.php"
    And wait for the page to be loaded
    Then I should see "Class 1"
    When I click the "i.mdi-delete" element
    And I confirm the popup
    Then I should not see "Class 1"
