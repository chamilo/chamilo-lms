# Ported from tests/behat/features/profile.feature with real drift + a Behat bug fix:
#
# - "Edit profile" (UserProfileCard.vue) is a real `<a href="/account/edit">`
#   router-link, not a `<button>` — confirmed live via a real DOM check
#   ("I follow", not "I press"; the earlier "I press" version could never
#   actually resolve it through pressButton()'s button/input[submit]-only
#   final fallback, which explains a real CI failure hanging the full test
#   timeout on this exact step). Navigates to /account/edit (Symfony
#   ProfileType form), not a SPA-only path.
# - Field ids remain profile_firstname / profile_lastname (Symfony form block
#   prefix "profile"); submit is still name="update_profile".
# - **Original Behat assertion bug fixed**: after restoring firstname/lastname to
#   Andrea/Costea (acostea's real seed name), the scenario still asserted
#   "Andrew"/"Doe". That could never pass once the restore worked. The final
#   Then now asserts the restored values, which is the scenario's documented
#   intent ("Change … then restore").
Feature: Profile page
  A student should update his profile information.

  Background:
    Given I am a student

  Scenario: Change user first name with Andrew then restore to Andrea
    Given I am on "/account/home"
    And I wait for the page to be loaded
    And I follow "Edit profile"
    And I wait for the page to be loaded
    And I fill in the following:
      | profile_firstname | Andrew |
      | profile_lastname  | Doe    |
    And I press "update_profile"
    And I wait for the page to be loaded
    And I follow "Edit profile"
    And I wait for the page to be loaded
    Then I should see "Andrew"
    And I should see "Doe"
    And I wait for the page to be loaded
    And I fill in the following:
      | profile_firstname | Andrea |
      | profile_lastname  | Costea |
    And I press "update_profile"
    And wait for the page to be loaded
    And I follow "Edit profile"
    And wait for the page to be loaded
    Then I should see "Andrea"
    And I should see "Costea"
    And I should not see an error
