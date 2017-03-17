Feature: User extra fields
    In order to use the user extra fields
    As an administrator
    I need to be able to create an extra field

  Scenario: Create a text extra field
      Given I am a platform administrator
      And I am on "/main/admin/extra_fields.php?type=user&action=add"
      When I fill in the following:
          | display_text          | Behat extra field               |
          | variable      | behat_extra_field               |
      And I fill in select bootstrap static input "#field_type" select "1"
      And I press "submit"
      Then I should see "Item added"
