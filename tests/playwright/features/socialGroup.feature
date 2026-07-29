# Ported from tests/behat/features/socialGroup.feature.
#
# - group_add.php is still the live legacy page; "description" is a TinyMCE
#   field bound via window.setContentFromEditor (same helper career.feature
#   uses), handled by the existing "I fill in editor field ... with ..." step.
# - "Invite a friend to group" hardcodes social group id "1" and friend id
#   "11" (fbaggins), same category of assumption as the documented cid=1
#   policy: correct only on a FRESH install where this is the first-ever
#   usergroup row and fbaggins is the 11th seeded user — true for real CI,
#   not for this shared/long-lived dev box (confirmed via a direct query:
#   this box's usergroup table already has ids 22/24/25 from class.feature's
#   own runs, and fbaggins is really id 13 here). Kept as the original
#   literal values, matching the standing policy — not renumbered to fit this
#   box. Local verification of this specific scenario needs a throwaway
#   substitution of the real current ids, same as the TEMP/cid=1 case.
# - The three commented-out scenarios in the original (accept/deny invitation,
#   delete member) are dropped, not ported — they were already inert in the
#   original Behat suite too.
Feature: Social Group
  In order to use the Social Network
  As an administrator
  I need to be able to create a social group, invite users and post a message

  Scenario: Create a social group
    Given I am a platform administrator
    And I am on "/main/social/group_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Behat Test Group |
    Then I fill in editor field "description" with "This is a group created by Behat"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Invite a friend to group
    Given I am a platform administrator
    And I have a friend named "fbaggins" with id "11"
    When I invite to a friend with id "11" to a social group with id "1"
    Then I should see "Invitation sent"
