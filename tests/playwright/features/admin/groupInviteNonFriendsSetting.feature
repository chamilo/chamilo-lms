@common @groupinvite
Feature: Setting to allow inviting non-friends to social groups
  Administrators decide whether a group admin may invite only friends (default) or any user.

  Scenario: Administrator can see the setting in the group settings
    Given I am a platform administrator
    And I am on "/admin/settings/group"
    And I wait for the page to be loaded
    Then I should see "Allow inviting non-friends to social groups"

  Scenario: Student cannot access the group settings
    Given I am a student
    And I am on "/admin/settings/group"
    And I wait for the page to be loaded
    Then I should not see "Allow inviting non-friends to social groups"
