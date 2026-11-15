@common @tools @gh3018
Feature: Track ungraded Hot Potatoes attempts
  Course teachers can inspect launches and learner-acknowledged completions without creating grades.

  # The teacher below must belong to TEMP to manage Hot Potatoes: on a freshly seeded instance she is not subscribed. Idempotent
  # ("Register" is not shown when she already is), and she stays subscribed for the other features.
  Scenario: Subscribe the teacher to TEMP
    Given I am a platform administrator
    And I am on "/main/user/subscribe_user.php?keyword=mmosquera&type=1&cid=3"
    And wait for the page to be loaded
    Then I follow "Register" if it is visible
    And wait very long for the page to be loaded

  Scenario: Teacher launches an activity, finishes it, and sees the recorded attempt
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I press "Hot Potatoes"
    When I import a disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should be listed
    When I complete and inspect the disposable Hot Potatoes attempt
    Then I should see "Activity attempts and teacher-assessed scores (latest 100)"
    When I remove the disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should no longer be listed
