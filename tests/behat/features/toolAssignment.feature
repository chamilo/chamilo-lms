Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Scenario: Create a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-folder-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | name | Work 1 |
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Assignment created"

  Scenario: Edit a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And I wait for the page to be loaded
    Then I should see "Work description"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    When I fill in the following:
      | name | Assignment name |
    Then I should see "Assignment name"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Assignment Updated"

  Scenario: Edit maximum score
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And I wait for the page to be loaded
    Then I should see "Assignment name"
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    When I fill in the following:
      | qualification  | 20 |
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Assignment Updated"


  Scenario: Send work as student (acostea)
    Given I am a platform administrator
    And I am not logged
    And I am a student
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    And wait very long for the page to be loaded
    And I should see "Assignment name"
    Then I click the "span.p-button-label" element
    And wait very long for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "files[]"
    And wait very long for the page to be loaded
    Then I should see "favicon.ico"

  Scenario: Check that work previously uploaded by student is available for the teacher.
    Given I am not logged
    Given I am a platform administrator
    And I am not logged
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And wait very long for the page to be loaded
    And wait very long for the page to be loaded
    Then I should see "Work description"
    And I should see "favicon"

  Scenario: Add a comment and a attachment to the work previously uploaded by student
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I should see "Assignment name"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-reply-all" element
    And I wait for the page to be loaded
    When I fill in the following:
      | assignment-comment | Nice |
      | qualification | 20 |
    Then I wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "assignment-attach-correction"
    And I press "assignment-send"
    And I wait for the page to be loaded
    Then I should see "comment added successfully"
    And I should not see an error

  Scenario: Admin views submission list for Work 1
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I should see "Work 1"
    Then I click the "i.mdi-format-list-bulleted" element
    And wait very long for the page to be loaded
    Then I should see "Alan"
    And I should see "Garcia"

  Scenario: Student sees graded score for Work 1
    Given I am not logged
    Given I am a platform administrator
    And I am not logged
    And I am a student
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I should see "Work 1"
    And I should see "20.0/20.0"

  Scenario: Admin views graded score for Work 1
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I should see "Work 1"
    And I should see "10.0/20.0"

  Scenario: Admin deletes Work 1 from assignments list
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Assignments"
    And wait very long for the page to be loaded
    Then I should see "Work 1"
    And I click the "input.p-checkbox-input" element
    And I click the "span.mdi-delete" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see "Work 1"
    And I should not see an error
