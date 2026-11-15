@common @tools @gh3018
Feature: Hot Potatoes in Exercises
  Teachers can import Hot Potatoes from Exercises, while students can open the published activities.

  # The teacher below must belong to TEMP to manage Hot Potatoes: on a freshly seeded instance she is not subscribed. Idempotent
  # ("Register" is not shown when she already is), and she stays subscribed for the other features.
  Scenario: Subscribe the teacher to TEMP
    Given I am a platform administrator
    And I am on "/main/user/subscribe_user.php?keyword=mmosquera&type=1&cid=3"
    And wait for the page to be loaded
    Then I follow "Register" if it is visible
    And wait very long for the page to be loaded

  Scenario: Teacher can open the Hot Potatoes import form
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    When I press "Hot Potatoes"
    Then I should see "Choose file"
    And I should see "Import Hot Potatoes"

  Scenario: Student can find Hot Potatoes but cannot import
    Given I am a student
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    When I press "Hot Potatoes"
    Then I should not see "Import Hot Potatoes"
