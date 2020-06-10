Feature: Profile page
  A student should update his profile information.

  Background:
    Given I am a student

  Scenario: Update profile with first name Andrew then restore Andrea
    Given I am on "/main/auth/profile.php"
    When I fill in the following:
      | firstname | Andrew |
    And I press "Save settings"
    And wait for the page to be loaded
    Then I should see "Your new profile has been saved"
    Then I am on "/main/social/home.php"
    And I should see "Andrew"
    Then I am on "/main/auth/profile.php"
    Then I fill in the following:
      | firstname | Andrea |
    And I press "Save settings"
    Then I should see "Your new profile has been saved"
    Then I am on "/main/social/home.php"
    Then I should see "Andrea"