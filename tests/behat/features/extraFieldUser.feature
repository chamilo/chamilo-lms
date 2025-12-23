Feature: User extra fields
    In order to use the user extra fields
    As an administrator
    I need to be able to create an extra field

  Scenario: Create a text extra field
      Given I am a platform administrator
      And I am on "/main/admin/extra_fields.php?type=user&action=add"
      And wait for the page to be loaded
      When I fill in the following:
          | display_text          | Behat extra field               |
          | variable      | behat_extra_field               |
      And I fill in select bootstrap static input "#value_type" select "1"
      And I press "submit"
      And wait for the page to be loaded
      Then I click the "th.ui-th-column" element
      And I wait for the page to be loaded
      And I should see "Behat extra field"
      And I should not see an error
