@common
Feature: Portal registration is independent from Chamilo update checks
  Scenario: Administrators can find portal registration and the modern updater
    Given I am a platform administrator
    And I am on "/admin"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Portal registration"
    And I should see the "a[href='/admin/system-update']" element

  Scenario: Administrators can configure automatic update checks separately
    Given I am a platform administrator
    And I am on "/admin/settings/admin"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Automatic update checks"
