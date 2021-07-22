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
    # The disable function works, it's the behat test that do no activate the function on the correct line
    #  Scenario: Disable a skilldis
    #    Given I am a platform administrator
    #    And I am on "main/admin/skill_list.php"
    #    And wait for the page to be loaded
    #    Then I should see "skilldis"
    #    Then I follow "Disable"
    #    And wait for the page to be loaded
    #    Then I should see "Skill "skilldis" disabled"

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

