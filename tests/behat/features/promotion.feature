Feature: Promotions
    In order to use the promotion feature
    As an administrator
    I need to be able to create a promotion

  Scenario: Create a Promotion
      Given I am a platform administrator
      And I am on "/main/admin/promotions.php?action=add"
      When I fill in the following:
          | name          | Promotion 2030               |
      And I fill in ckeditor field "description" with "Promotion description"
      And I fill in select bootstrap static input "#career_id" select "1"
      And I press "submit"
      Then I should see "Item added"
