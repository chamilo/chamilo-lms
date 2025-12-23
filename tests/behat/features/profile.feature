Feature: Profile page
  A student should update his profile information.

  Background:
    Given I am a student

  Scenario: Change user first name with Andrew then restore to Andrea
    Given I am on "/account/home"
    And I wait for the page to be loaded
    And I press "Edit profile"
    And I wait for the page to be loaded
    And I fill in the following:
      | profile_firstname | Andrew |
      | profile_lastname  | Doe    |
    And I press "update_profile"
    And I wait for the page to be loaded
    And I press "Edit profile"
    And I wait for the page to be loaded
    Then I should see "Andrew"
    And I should see "Doe"
    And I wait for the page to be loaded
    And I fill in the following:
      | profile_firstname | Andrea |
      | profile_lastname  | Costea |
    And I press "update_profile"
    And wait for the page to be loaded
    And I press "Edit profile"
    And wait for the page to be loaded
    Then I should see "Andrew"
    And I should see "Doe"
    And I should not see an error


