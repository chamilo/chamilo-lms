Feature: Settings update
  In order to use Chamilo
  As an administrator
  I need to be able to update Chamilo settings

  Scenario: Update 'profile' setting
    Given I am a platform administrator
    And I am on "/main/admin/settings.php?category=User"
    And I check "Name"
    And I check "e-mail"
    And I check "Code"
    And I check "Login"
    And I press "Save settings"
    Then I should see "Update successful"

  Scenario: Update 'allow_registration' setting
    Given I am a platform administrator
    And I am on "/main/admin/settings.php"
    And I check the "allow_registration" radio button with "true" value
    And I press "Save settings"
    Then I should see "Update successful"