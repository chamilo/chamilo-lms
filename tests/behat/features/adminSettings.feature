Feature: Settings update
  In order to use Chamilo
  As an administrator
  I need to be able to update Chamilo settings

  Scenario: Update 'profile' setting
    Given I am a platform administrator
    And I am on "/public/admin/settings/search_settings?keyword=changeable_options"
    And I select "Name" from "form_changeable_options"
    And I additionally select "E-mail" from "form_changeable_options"
    And I additionally select "Official code" from "form_changeable_options"
    And I additionally select "Login" from "form_changeable_options"
    And I press "Save"
    Then I should see "Settings have been successfully updated"

  Scenario: Update 'allow_registration' setting
    Given I am a platform administrator
    And I am on "/public/admin/settings/search_settings?keyword=allow_registration"
    And I select "Yes" from "form_allow_registration"
    And I press "Save"
    Then I should see "Settings have been successfully updated"

  Scenario: Update 'allow_group_categories' setting
    Given I am a platform administrator
    And I am on "/public/admin/settings/search_settings?keyword=allow_group_categories"
    And I select "Yes" from "form_allow_group_categories"
    And I press "Save"
    Then I should see "Settings have been successfully updated"
