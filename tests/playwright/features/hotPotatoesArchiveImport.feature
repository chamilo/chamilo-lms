@common @tools @gh3018
Feature: Hot Potatoes in Exercises
  Teachers can import Hot Potatoes from Exercises, while students can open the published activities.

  Scenario: Teacher can open the Hot Potatoes import form
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    When I press "Hot Potatoes"
    Then I should see the "input[name='package']" element
    And I should see "Import Hot Potatoes"

  Scenario: Student can find Hot Potatoes but cannot import
    Given I am a student
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    When I press "Hot Potatoes"
    Then I should not see "Import Hot Potatoes"
