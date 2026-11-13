@common @tools @gh3018
Feature: Remove unused Hot Potatoes archives safely
  Teachers can remove unreferenced course links without deleting shared archive content.

  Scenario: Teacher imports and removes a disposable unreferenced archive
    # mmosquera is not subscribed to TEMP on a freshly seeded instance, so the management UI needs the platform admin.
    Given I am a platform administrator
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
