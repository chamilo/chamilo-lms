Feature: Promotions
  In order to use the promotion feature
  As an administrator
  I need to be able to create a promotion

  Scenario: Create a promotion
    Given I am a platform administrator
    And I am on "/main/admin/promotions.php?action=add"
    And wait for the page to be loaded
    When I fill in the following:
      | title       | Developer        |
    And I fill in editor field "description" with "Promotion Description"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Developer"
    And I should not see an error


  Scenario: Edit a promotion
    Given I am a platform administrator
    And I am on "/main/admin/promotions.php"
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I should see "Developer"
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in editor field "description" with "Promotion Description edited"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Developer"

  Scenario: Copy a promotion
    Given I am a platform administrator
    And I am on "/main/admin/promotions.php"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Developer"
    And I click the "i.mdi-text-box-plus" element
    And I confirm the popup
    Then I should not see an error
    And I should see "Developer Copy"


  Scenario: Delete a promotion
    Given I am a platform administrator
    And I am on "/main/admin/promotions.php"
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
