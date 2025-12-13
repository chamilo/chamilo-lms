Feature: Career
    In order to use the careers
    As an administrator
    I need to be able to manage careers

  Scenario: Create a career
      Given I am a platform administrator
      And I am on "/main/admin/career_dashboard.php"
      Then I should not see an error
      And I am on "/main/admin/careers.php?action=add"
      And wait for the page to be loaded
      When I fill in the following:
          | career_title          | Developer               |
      And I fill in editor field "description" with "Description"
      And I press "submit"
      And I wait for the page to be loaded
      Then I should see "Developer"
      And I should not see an error

  Scenario: Edit a career
    Given I am a platform administrator
    And I am on "/main/admin/careers.php"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Developer"
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in editor field "description" with "Description edited"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Developer"

  Scenario: Copy a career
    Given I am a platform administrator
    And I am on "/main/admin/careers.php"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Developer"
    And I click the "i.mdi-text-box-plus" element
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Developer Copy"

  Scenario: Delete a career
      Given I am a platform administrator
      And I am on "/main/admin/careers.php"
      And I wait for the page to be loaded
      Then I should not see an error
      And I should see "Developer"
      And I click the "i.mdi-delete" element
      And I confirm the popup
      And I wait for the page to be loaded
      Then I should not see an error
      And I should see "Developer"
      And I click the "i.mdi-delete" element
      And I confirm the popup
      And I wait for the page to be loaded
      Then I should not see an error
      And I should not see "Developer"

