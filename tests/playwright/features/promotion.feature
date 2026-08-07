# Ported from tests/behat/features/promotion.feature (near-clone of career.feature).
#
# - promotions.php is still the live legacy jqGrid page; form fields are title,
#   description (TinyMCE), career_id (required select of existing careers).
# - Promotions are sub-elements of careers: create form hard-requires a career.
#   career.feature deletes its "Developer" career at the end, so this feature
#   creates its own career first ("PromoCareer") rather than depending on
#   leftover career data or scenario order across files/workers.
# - Row actions use the same mdi icons + native confirm() as careers.php.
#   Edit/Copy/Delete use the row-scoped icon step so pre-existing promotions
#   on a dirty local box are not hit by a blind .first().
Feature: Promotions
  In order to use the promotion feature
  As an administrator
  I need to be able to create a promotion

  Scenario: Create a promotion
    Given I am a platform administrator
    # Prerequisite: at least one career must exist for the career_id select.
    And I am on "/main/admin/careers.php?action=add"
    And wait for the page to be loaded
    When I fill in the following:
      | career_title | PromoCareer |
    And I fill in editor field "description" with "Career for promotion tests"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "PromoCareer"
    And I am on "/main/admin/promotions.php?action=add"
    And wait for the page to be loaded
    When I fill in the following:
      | title       | Developer        |
    And I select "PromoCareer" from "career_id"
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
    And I click the "i.mdi-pencil" icon in the row for "Developer"
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
    And I click the "i.mdi-text-box-plus" icon in the row for "Developer"
    And I confirm the popup
    Then I should not see an error
    And I should see "Developer Copy"


  Scenario: Delete a promotion
    Given I am a platform administrator
    And I am on "/main/admin/promotions.php"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Developer"
    And I click the "i.mdi-delete" icon in the row for "Developer Copy"
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see an error
    And I should not see "Developer Copy"
    And I click the "i.mdi-delete" icon in the row for "Developer"
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see an error
    And I should not see "Developer"
