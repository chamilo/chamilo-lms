Feature: Profile page
  A student should update his profile information.

  Background:
    Given I am a student

  Scenario: Update profile with first name Andrew then restore Andrea
    Given I am on "/account/home"
    Then I should see "Profile"
    Then I follow "Edit profile"
    Then I fill in the following:
      | profile_firstname | Andrew |
    And I press "Save"
    And wait for the page to be loaded
    Then I should see "Updated"
    And I should see "Andrew"
    Then I follow "Edit profile"
    Then I fill in the following:
      | profile_firstname | Andrea |
    And I press "Save"
    Then I should see "Updated"
    Then I am on "/main/social/home.php"
    Then I should see "Andrea"