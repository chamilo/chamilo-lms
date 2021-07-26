Feature: Skills
  In order to use the skills
  As an administrator
  I need to be able to create skills

  Scenario: Create a skill skill1
    Given I am a platform administrator
    And I am on "main/admin/skill_create.php"
    When I fill in the following:
      | name | skill1 |
      | short_code | s1 |
      | description | description |
      | criteria | criteria |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "The skill has been created: skill1"

  Scenario: Create a second level skill
    Given I am a platform administrator
    And I am on "main/admin/skill_create.php"
    When I fill in the following:
      | name | skill11 |
      | short_code | s11 |
      | description | description 11 |
      | criteria | criteria 11 |
    Then I select "skill1" from "parent_id"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "The skill has been created: skill11"

  Scenario: Create a skill skilldis
    Given I am a platform administrator
    And I am on "main/admin/skill_create.php"
    When I fill in the following:
      | name | skilldis |
      | short_code | sdis |
      | description | description |
      | criteria | criteria |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "The skill has been created: skilldis"

    # This following scenario needs to be adapted because 
    # the first skill in the list is disable and not the one named skilldis
    # So Intead of having then I follow "Disable" I put the exact page "/main/admin/skill_list.php?id=4&action=disable" 
    # where I should get because there is nothing unique to identify this link other than the URL
    # The problem is that it will only work if the there was no skills created before lauching the behat tests
    # The disable function works, it's the behat test that do no activate the function on the correct line
  Scenario: Disable a skill skilldis
    Given I am a platform administrator
    And I am on "main/admin/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skilldis"
    Then I am on "/main/admin/skill_list.php?id=4&action=disable"
    And wait for the page to be loaded
    Then I should see "Skill \"skilldis\" disabled"

    # This following scenario needs to be adapted because 
    # the first skill in the list is tried to be enable and not the one named skilldis
    # So Intead of having then I follow "Enable" I put the exact page "/main/admin/skill_list.php?id=4&action=enable" 
    # where I should get because there is nothing unique to identify this link other than the URL
    # The problem is that it will only work if the there was no skills created before lauching the behat tests
    # The enable function works, it's the behat test that do no activate the function on the correct line
  Scenario: Enable a skill skilldis
    Given I am a platform administrator
    And I am on "main/admin/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skilldis"
    Then I am on "/main/admin/skill_list.php?id=4&action=enable"
    And wait for the page to be loaded
    Then I should see "Skill \"skilldis\" enabled"

    # This scenario works but it needs to be adapted 
    # because it does not update skill1 but the first in the list
  Scenario: Update a skill skill1
    Given I am a platform administrator
    And I am on "main/admin/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skill1"
    Then I follow "Edit"
    When I fill in the following:
      | name | skill1 Edited |
      | description | description Edited |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "The skill has been updated"

  Scenario: Assign skill11 to user 1
    Given I am a platform administrator
    And I am on "main/badge/assign.php?user=1"
    When I select "skill11" from "skill"
    And wait for the page to be loaded
    Then I fill in the following:
	    | argumentation | argumentation |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "To assign a new skill to this user, click here"
    Then I should see "The skill skill11 has been assigned to user John Doe"

  Scenario: Reassign skill11 to user 1
    Given I am a platform administrator
    And I am on "main/badge/assign.php?user=1"
    When I select "skill11" from "skill"
    And wait for the page to be loaded
    Then I fill in the following:
	    | argumentation | argumentation |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "The user John Doe has already achieved the skill skill11"

# The following scenario need to be completed once the funcionality is ready
#  Scenario: View assign skill
#    Given I am a platform administrator
#    And I am on "/badge/3/user/1"
#    Then I should see "..."
#
# The following scenario need to be completed once the funcionality is ready
#  Scenario: Set a badge to a skill
#    Given I am a platform administrator
#    And I am on "main/admin/skill_list.php"
#    Then I should see "skill11"
#    Then I follow "Create badge"
