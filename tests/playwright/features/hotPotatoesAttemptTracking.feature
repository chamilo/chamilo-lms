@common @tools @gh3018
Feature: Track ungraded Hot Potatoes attempts
  Course teachers can inspect launches and learner-acknowledged completions without creating grades.

  Scenario: Teacher launches an activity, finishes it, and sees the recorded attempt
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I press "Hot Potatoes"
    When I import a disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should be listed
    When I complete and inspect the disposable Hot Potatoes attempt
    Then I should see "Ungraded activity attempts (latest 100)"
    When I remove the disposable Hot Potatoes archive
    Then the disposable Hot Potatoes archive should no longer be listed
