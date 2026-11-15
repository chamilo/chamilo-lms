@common @tools @gh3018
Feature: Remove unused Hot Potatoes archives safely
  Teachers can remove unreferenced course links without deleting shared archive content.

  # The teacher below must belong to TEMP to manage Hot Potatoes: on a freshly seeded instance she is not subscribed. Idempotent
  # ("Register" is not shown when she already is), and she stays subscribed for the other features.
  Scenario: Subscribe the teacher to TEMP
    Given I am a platform administrator
    And I am on "/main/user/subscribe_user.php?keyword=mmosquera&type=1&cid=3"
    And wait for the page to be loaded
    Then I follow "Register" if it is visible
    And wait very long for the page to be loaded

  Scenario: Teacher imports and removes a disposable unreferenced archive
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I press "Hot Potatoes"
    When I import a disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should be listed
    When I remove the disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should no longer be listed

  Scenario: Student cannot see Hot Potatoes delete actions
    Given I am a student
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I press "Hot Potatoes"
    Then I should not see the "button[aria-label='Delete']" element
