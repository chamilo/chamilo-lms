Feature: Submit assignments as HTML
  Online text submissions should be stored as HTML and reviewable without downloading.

  Scenario: Student submits rich HTML and the teacher reviews it online
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I press "Create Assignment"
    And wait for the page to be loaded
    When I fill in the following:
      | Assignment name | HTML Submission 8287 |
    And I fill in the active tinymce editor with "Instructions for HTML submission"
    And I press "Advanced settings"
    And I select the dropdown option "Allow only text" in "allow-text-assignment"
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Assignment created"

    Given I am not logged
    And I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "HTML Submission 8287"
    And wait for the page to be loaded when ready
    Then I press "Write my submission"
    And wait for the page to be loaded when ready
    And I fill in the active tinymce editor with "Formatted HTML answer 8287"
    And I press "Submit"
    And wait for the page to be loaded when ready
    Then I should see "Text submitted successfully"

    Given I am not logged
    And I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "HTML Submission 8287"
    And wait for the page to be loaded when ready
    Then I should see "HTML Submission 8287.html"
    And I press "Correct and grade"
    Then I should see "Formatted HTML answer 8287"

    Given I am not logged
    And I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I click the "input.p-checkbox-input" icon in the row for "HTML Submission 8287"
    And I press "Delete selected"
    And I press "Yes"
    And wait for the page to be loaded when ready
    Then I should not see "HTML Submission 8287"
