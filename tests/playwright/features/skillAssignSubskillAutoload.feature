# With skill.manual_assignment_subskill_autoload, the skill assignment form walks the skills tree
# as main/skills/assign.php did: the first select only lists the first level, and each selected
# skill adds a select with its own children. The assigned skill is the deepest one selected, so a
# skill can be assigned at any depth. A structural skill that cannot be acquired is listed too,
# since it leads to its children, but it cannot be saved. The Vue form used to list every skill
# in a single select whatever the setting.
#
# acostea is the learner the skills are assigned to. Skills cannot be deleted, only disabled: the
# last scenario disables the three it creates, after the setting is back to its default.
# The assignment form loads its data after the page itself: every step that acts on it first waits
# for an element only the loaded data renders. On a development server these API calls queue on the
# session and can take a minute, hence @slow-scenario.
@slow-scenario
Feature: Assign a skill through the skills tree
  In order to find a skill among many
  As a platform administrator
  I want to choose the skill to assign level by level

  Scenario: Admin creates a structural skill at the first level
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title      | Cascade structure skill |
      | short_code | cass                    |
    # "acquirable" is the id of the checkbox's wrapper, not of the checkbox, and the box is ticked by
    # default: clicking it unticks it. The scenarios below see the result as "cannot be acquired".
    And I click the "input[name='acquirable']" element
    And I press "submit"
    # The creation redirects to the skills wheel, whose 3D canvas a headless browser may never finish
    And I am on "main/skills/skill_list.php"
    Then I wait up to 90 seconds for the element "text=Cascade structure skill" to appear

  Scenario: Admin creates a skill under the structural skill
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title      | Cascade middle skill |
      | short_code | casm                 |
    And I select "Cascade structure skill" from "parent_id"
    And I press "submit"
    And I am on "main/skills/skill_list.php"
    Then I wait up to 90 seconds for the element "text=Cascade middle skill" to appear

  Scenario: Admin creates a skill under the middle skill
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title      | Cascade leaf skill |
      | short_code | casl               |
    And I select "Cascade middle skill" from "parent_id"
    And I press "submit"
    And I am on "main/skills/skill_list.php"
    Then I wait up to 90 seconds for the element "text=Cascade leaf skill" to appear

  Scenario: Admin turns on the subskill autoload
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=manual_assignment_subskill_autoload"
    And I wait up to 90 seconds for the element "#form_manual_assignment_subskill_autoload" to appear
    When I select "Yes" from "form_manual_assignment_subskill_autoload"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then the field "form_manual_assignment_subskill_autoload" should have value "true"

  Scenario: The first select only lists the first level of the skills tree
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    When I click the "#skill-assignment-skillId" element
    And I wait for the element ".p-select-overlay" to appear
    Then I should see the ".p-select-overlay li:has-text('Cascade structure skill')" element
    And I should not see the ".p-select-overlay li:has-text('Cascade middle skill')" element
    And I should not see the ".p-select-overlay li:has-text('Cascade leaf skill')" element

  # The structural skill leads to its children but cannot be assigned itself.
  Scenario: A structural skill shows its children and cannot be saved
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    When I select the dropdown option "Cascade structure skill" in "skill-assignment-skillId"
    And I wait up to 90 seconds for the element "#skill-assignment-subSkillId1" to appear
    Then I should see "This skill cannot be acquired"
    And I should not see the "[name='argumentation']" element
    When I click the "#skill-assignment-subSkillId1" element
    And I wait for the element ".p-select-overlay" to appear
    Then I should see the ".p-select-overlay li:has-text('Cascade middle skill')" element
    And I should not see the ".p-select-overlay li:has-text('Cascade leaf skill')" element

  # The middle skill has a child of its own: leaving that third select empty assigns the middle skill.
  Scenario: Admin assigns the middle skill without going down to the leaf
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    And I select the dropdown option "Cascade structure skill" in "skill-assignment-skillId"
    And I wait up to 90 seconds for the element "#skill-assignment-subSkillId1" to appear
    When I select the dropdown option "Cascade middle skill" in "skill-assignment-subSkillId1"
    And I wait up to 90 seconds for the element "#skill-assignment-subSkillId2" to appear
    Then I should not see "This skill cannot be acquired"
    When I fill in "argumentation" with "Assigned one level above the leaf"
    And I press "Save"
    Then I wait up to 90 seconds for the element "text=Skill acquired" to appear

  Scenario: Admin removes the middle skill from the learner
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    And I select the dropdown option "Cascade structure skill" in "skill-assignment-skillId"
    And I wait up to 90 seconds for the element "#skill-assignment-subSkillId1" to appear
    And I select the dropdown option "Cascade middle skill" in "skill-assignment-subSkillId1"
    And I wait up to 90 seconds for the element "text=Skill acquired" to appear
    When I press "Remove"
    And I press "Yes"
    Then I wait until I no longer see "Skill acquired"

  Scenario: Admin restores the subskill autoload to its default
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=manual_assignment_subskill_autoload"
    And I wait up to 90 seconds for the element "#form_manual_assignment_subskill_autoload" to appear
    When I select "No" from "form_manual_assignment_subskill_autoload"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then the field "form_manual_assignment_subskill_autoload" should have value "false"

  # Without the setting, the single select lists every acquirable skill, the leaf included.
  Scenario: Without the setting, every acquirable skill is in one select
    Given I am a platform administrator
    And I resolve the user id for "acostea"
    And I am on "/skill/assign/USER_ID" with the resolved user id
    And I wait up to 90 seconds for the element "text=Andrea Costea" to appear
    When I click the "#skill-assignment-skill" element
    And I wait for the element ".p-select-overlay" to appear
    Then I should see the ".p-select-overlay li:has-text('Cascade leaf skill')" element
    And I should not see the ".p-select-overlay li:has-text('Cascade structure skill')" element

  Scenario: Admin disables the three skills
    Given I am a platform administrator
    And I am on "main/skills/skill_list.php"
    And I wait up to 90 seconds for the element "text=Cascade leaf skill" to appear
    When I click the "a[title='Disable']" icon in the row for "Cascade leaf skill"
    And I wait up to 90 seconds for the element "text=Cascade middle skill" to appear
    And I click the "a[title='Disable']" icon in the row for "Cascade middle skill"
    And I wait up to 90 seconds for the element "text=Cascade structure skill" to appear
    And I click the "a[title='Disable']" icon in the row for "Cascade structure skill"
    And I wait for the page to be loaded
    Then I should not see an error
