Feature: Assessments tool
  Manage assessment settings within a course

  Scenario: Set certification minimum score to 50 in course TEMP
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    When I fill in the following:
      | edit_cat_form_certif_min_score | 50 |
    And I press "edit_cat_form_submit"
    And I wait for the page to be loaded
    Then I should see "50"

  Scenario: Create an evaluation "exam" in course TEMP
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-table-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | weight_mask | 50 |
      | add_eval_form_max | 10 |
      | evaluation_title | exam |
      | min_score | 3 |
    And I check "Grade learners"
    And I press "add_eval_form_submit"
    And I wait for the page to be loaded
    When I fill in the following:
      | score[5] | 10 |
    And I press "add_result_form_submit"
    And I wait for the page to be loaded
    Then I should see "exam"

  Scenario: Create a work
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait very long for the page to be loaded
    Then I click the "span.mdi-folder-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | name | Work 1 |
    And I press "submit"

  Scenario: Edit maximum score
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
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


  Scenario: Send work as student (acostea)
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I click the "span.p-button-label" element
    And wait very long for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "files[]"


  Scenario: Add a comment and a attachment to the work previously uploaded by student
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "a.text-blue-600" element
    And wait very long for the page to be loaded
    Then I click the "span.mdi-reply-all" element
    And I wait for the page to be loaded
    When I fill in the following:
      | assignment-comment | Nice |
      | assignment-score | 20 |
    Then I wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "assignment-attach-correction"
    And I press "assignment-send"

  Scenario: Link an Assignment to the evaluation and edit its min score
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-link-plus" element
    And I wait for the page to be loaded
    When I select "Assignments" from "create_link_select_link"
    And wait very long for the page to be loaded
    When I fill in the following:
      | weight_mask | 50 |
      | minimum score | 3 |
    And I press "add_link_submit"
    And I wait for the page to be loaded
    Then I follow "Edit weight"
    And I wait for the page to be loaded
    When I fill in the following:
      | min_score | 3 |
    And I press "edit_eval_form_submit"
    And I wait for the page to be loaded
    Then I should see "The evaluation has been successfully edited"

  Scenario: Edit a result and verify it in chart view
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    And I follow "exam"
    And I wait for the page to be loaded
    And I follow "Edit"
    And I wait for the page to be loaded
    When I fill in the following:
      | score | 8 |
    And I press "edit_result_form_submit"
    And I wait for the page to be loaded
    Then I follow "Assessments"
    And I click the "i.mdi-chart-box" element
    And wait very long for the page to be loaded
    Then I should see "8 / 10"

  Scenario: Open certificate from list view in Assessments
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-format-list-text" element
    And I wait for the page to be loaded
    Then I click the "i.mdi-certificate" element
    And I wait for the page to be loaded
    And I follow "certificate"
    And I wait for the page to be loaded
    Then I should see "certificate"

  Scenario: Admin exports all to PDF
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "i.mdi-account" element
    And I wait for the page to be loaded
    And I follow "Export all to PDF"
    Then I should not see an error

  Scenario: Deletes selected assessments
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "a.btn--action" element
    And I click the "button.justify-center" element
    And I wait for the page to be loaded
    And I follow "Delete selected"
    And I confirm the popup
    Then I should see "Deleted"
    And I should not see "exam"
    And I should not see "Work 1"
    And I should not see an error

  Scenario: Admin deletes Work 1 from assignments list
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I click the "input.p-checkbox-input" element
    And I click the "span.mdi-delete" element
    And I confirm the popup

