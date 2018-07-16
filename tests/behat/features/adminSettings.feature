Feature: Settings update
  In order to use Chamilo
  As an administrator
  I need to be able to update Chamilo settings

  Scenario: Update 'profile' setting
    Given I am a platform administrator
    Then I am on "/main/admin/settings.php?search_field=profile&category=search_setting"
    And I check "Name"
    And I check "e-mail"
    And I check "Login"
    And I check "profile[officialcode]"
    And I press "Save settings"
    Then I should see "Update successful"

  Scenario: Update 'allow_registration' setting
    Given I am a platform administrator
    And I am on "/main/admin/settings.php"
    And I check the "allow_registration" radio button with "true" value
    And I press "Save settings"
    Then I should see "Update successful"

  Scenario: Update 'allow_group_categories' setting
    Given I am a platform administrator
    And I am on "/main/admin/settings.php?search_field=allow_group_categories&category=search_setting"
    And I check the "allow_group_categories" radio button with "true" value
    And I press "Save settings"
    Then I should see "Update successful"