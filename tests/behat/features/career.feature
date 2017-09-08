Feature: Career
    In order to use the Career
    As an administrator
    I need to be able to create a career

  Scenario: Create a Career
      Given I am a platform administrator
      And I am on "/main/admin/careers.php?action=add"
      When I fill in the following:
          | name          | Developer               |
      And I fill in ckeditor field "description" with "Description"
      And I press "submit"
      Then I should see "Item added"
