# Ported from tests/behat/features/skill.feature.
#
# - All legacy pages confirmed still live (skill_create.php, skill_list.php,
#   skill_edit.php, assign.php, and /badge/{id}/user/{id}, a Symfony
#   BadgeController route that redirects to the equally-live issued_all.php).
# - Every install has exactly one pre-existing skill (id 1, "Root") — this
#   feature's own "skilldis"-is-id-4 assumption (see the original's own
#   comments, kept verbatim below) holds as long as skill1/skill11/skilldis
#   are the first 3 skills created after Root, same category of assumption
#   as the documented cid=3/TEMP policy elsewhere in this suite.
# - The original's last scenario ("Set a badge to a skill") is NOT ported —
#   its own comment doubly flags it ("need to be completed once the
#   funcionality is ready" + "a mettre en commentaire", i.e. "should be
#   commented out") and it drives a canvas-based custom badge-studio editor
#   plus a file upload, a much larger and more fragile automation surface
#   than the rest of this feature for a scenario the original authors
#   already flagged as not ready. "View assigned skill skill11 to user 1"
#   carries the same disclaimer comment but is a plain page-content check,
#   not canvas-based — kept, since nothing about it looks actually broken.
Feature: Skills
  In order to use the skills
  As an administrator
  I need to be able to create skills

  Scenario: Create a skill skill1
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title | skill1 |
      | short_code | s1 |
      | description | description |
      | criteria | criteria |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "skill1"

  Scenario: Create a second level skill
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title | skill11 |
      | short_code | s11 |
      | description | description 11 |
      | criteria | criteria 11 |
    Then I select "skill1" from "parent_id"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "skill11"

  Scenario: Create a skill skilldis
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title | skilldis |
      | short_code | sdis |
      | description | description |
      | criteria | criteria |
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "skilldis"

  # This following scenario needs to be adapted because
  # the first skill in the list is disable and not the one named skilldis
  # So Intead of having then I follow "Disable" I put the exact page "/main/skills/skill_list.php?id=4&action=disable"
  # where I should get because there is nothing unique to identify this link other than the URL
  # The problem is that it will only work if the there was no skills created before lauching the behat tests
  # The disable function works, it's the behat test that do no activate the function on the correct line
  Scenario: Disable a skill skilldis
    Given I am a platform administrator
    And I am on "main/skills/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skilldis"
    Then I am on "/main/skills/skill_list.php?id=4&action=disable"
    And wait for the page to be loaded
    Then I should not see an error

  # This following scenario needs to be adapted because
  # the first skill in the list is tried to be enable and not the one named skilldis
  # So Intead of having then I follow "Enable" I put the exact page "/main/skills/skill_list.php?id=4&action=enable"
  # where I should get because there is nothing unique to identify this link other than the URL
  # The problem is that it will only work if the there was no skills created before lauching the behat tests
  # The enable function works, it's the behat test that do no activate the function on the correct line
  Scenario: Enable a skill skilldis
    Given I am a platform administrator
    And I am on "main/skills/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skilldis"
    Then I am on "/main/skills/skill_list.php?id=4&action=enable"
    And wait for the page to be loaded
    Then I should not see an error

  # This scenario works but it needs to be adapted
  # because it does not update skill1 but the first in the list
  Scenario: Update a skill skill1
    Given I am a platform administrator
    And I am on "main/skills/skill_list.php"
    And wait for the page to be loaded
    Then I should see "skill1"
    Then I follow "Edit"
    And wait very long for the page to be loaded
    When I fill in the following:
      | title | skill1 Edited |
      | description | description Edited |
    And I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  # assign.php's success/already-achieved messages are both set via
  # $_SESSION['flash_message'] — a dead write nothing in the codebase ever
  # reads. Fixed to use Container::addFlash() (same fix already applied to
  # extra_fields.php), but per that fix's own documented finding, the
  # Symfony flash bag does not reliably survive a legacy header()+exit
  # redirect into the next request's #app[data-flashes] payload — so, same
  # as extraFieldUser.feature, asserting the redirect destination itself
  # (the durable signal) instead of the flash text.
  Scenario: Assign skill11 to user 1
    Given I am a platform administrator
    And I am on "main/skills/assign.php?user=1"
    When I select "skill11" from "skill"
    And wait very long for the page to be loaded
    Then I fill in the following:
      | argumentation | argumentation |
    And I press "save"
    And wait for the page to be loaded
    Then the URL should contain "myStudents.php"
    And I should see "s11"
    And I should not see an error

  Scenario: Reassign skill11 to user 1
    Given I am a platform administrator
    And I am on "main/skills/assign.php?user=1"
    And I wait for the page to be loaded
    When I select "skill11" from "skill"
    And wait very long for the page to be loaded
    Then I fill in the following:
      | argumentation | argumentation |
    And I press "save"
    And wait for the page to be loaded
    Then the URL should contain "assign.php"
    And I should not see an error

  Scenario: View assigned skill skill11 to user 1
    Given I am a platform administrator
    And I am on "/badge/3/user/1"
    And I wait for the page to be loaded
    Then I should see "Skill acquired"
    And I should see "John Doe"
