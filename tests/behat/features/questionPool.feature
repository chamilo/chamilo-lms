Feature: Question pool management for QP scenarios
  This feature contains scenarios to create courses, tests and questions
  for manual verification of the question pool workflow.

  Background:
    Given I am logged as "admin"
    And wait very long for the page to be loaded

  Scenario: Create course QP1
    Given I am on "/courses"
    And wait very long for the page to be loaded
    When I click the "span.mdi-plus" element
    And wait very long for the page to be loaded
    And I fill in the following:
      | course-name | QP1 |
    And I click the "span.mdi-plus" element
    And wait very long for the page to be loaded
    Then I should see "QP1"

  Scenario: Create course QP2
    Given I am on "/courses"
    And wait very long for the page to be loaded
    When I click the "span.mdi-plus" element
    And wait very long for the page to be loaded
    And I fill in the following:
      | course-name | QP2 |
    And I click the "span.mdi-plus" element
    And wait very long for the page to be loaded
    Then I should see "QP2"

  Scenario: Create a test and add question QPQUESTION1 (then delete)
    Given I am on course "QP1" homepage
    And wait very long for the page to be loaded
    When I follow "Tests"
    And wait very long for the page to be loaded
    And I click the "i.mdi-order-bool-ascending-variant" element
    And wait very long for the page to be loaded
    And I fill in the following:
      | exercise_title | QPTEST1 |
    And I click the "em.mdi-pencil" element
    And wait very long for the page to be loaded
    And I follow "Open question"
    And wait very long for the page to be loaded
    And I fill in the following:
      | questionName | QPQUESTION1 |
      | weighting    | 5 |
    And I press "question_admin_form_submitQuestion"
    And wait very long for the page to be loaded
    And I click the "a.delete-swal" element
    And I confirm the popup
    And I should not see an error

  Scenario: Upload answer (QPQUESTION2) and check recycle behavior
    Given I am on course "QP1" homepage
    And wait very long for the page to be loaded
    When I follow "Tests"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    When I follow "Upload Answer"
    And wait very long for the page to be loaded
    And I fill in the following:
      | questionName | QPQUESTION2 |
      | weighting    | 4 |
    And I press "question_admin_form_submitQuestion"
    And wait very long for the page to be loaded
    And I follow "Recycle existing questions"
    And wait very long for the page to be loaded
    Then I should see "QPQUESTION1"
    And I should not see "QPQUESTION2"
    When I click the "i.mdi-arrow-left" element
    And wait very long for the page to be loaded
    And I follow "Back to Test list"
    And wait very long for the page to be loaded
    And I click the "i.mdi-arrow-left" element
    And wait very long for the page to be loaded
    And I click the "i.mdi-database" element
    And wait very long for the page to be loaded
    Then I should see "QPQUESTION1"
    And I should see "QPQUESTION2"

  Scenario: Admin reviews question pool and filters by course
    Given I am on "/admin"
    And wait very long for the page to be loaded
    When I follow "Questions"
    And wait very long for the page to be loaded
    And I press "admin_question_submit"
    And wait very long for the page to be loaded
    Then I should see "QPQUESTION1"
    And I should see "QPQUESTION2"
    When I follow "QPQUESTION1"
    And wait very long for the page to be loaded
    Then I should see "Orphan question"
    When I follow "QPQUESTION2"
    And wait very long for the page to be loaded
    Then I should see "QPTEST1"
    When I select "QP2" from "selected_course"
    And I press "admin_question_submit"
    And wait very long for the page to be loaded
    Then I should not see "QPQUESTION1"
    And I should not see "QPQUESTION2"

  Scenario: Admin deletes course QP2 from course maintenance
    Given I am on course "QP1" homepage
    And wait very long for the page to be loaded
    And I click the "span.mdi-cog" element
    And wait very long for the page to be loaded
    And I follow "Course maintenance"
    And wait very long for the page to be loaded
    And I click the "i.mdi-trash-can-outline" element
    And wait very long for the page to be loaded
    And I check "delete-docs"
    And I fill in the following:
      | course_code_delete | QP2 |
    And I click the "button.btn-danger" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see "QP2"

  Scenario: Admin removes questions and database entries for QP1
    Given I am on course "QP1" homepage
    And wait very long for the page to be loaded
    When I follow "Tests"
    And wait very long for the page to be loaded
    And I click the "i.mdi-pencil" element
    And wait very long for the page to be loaded
    And I click the "a.delete-swal" element
    And I confirm the popup
    And wait very long for the page to be loaded
    And I click the "i.mdi-arrow-left" element
    And wait very long for the page to be loaded
    And I click the "i.mdi-delete" element
    And I confirm the popup
    And wait very long for the page to be loaded
    And I click the "i.mdi-database" element
    And wait very long for the page to be loaded
    And I press "delete"
    And I confirm the popup
    And wait very long for the page to be loaded
    And I press "delete"
    And I confirm the popup
    And wait very long for the page to be loaded

  Scenario: Admin deletes course QP1 via Course maintenance and checks QP2 absence
    Given I am on course "QP1" homepage
    And wait very long for the page to be loaded
    When I click the "span.mdi-cog" element
    And wait very long for the page to be loaded
    And I follow "Course maintenance"
    And wait very long for the page to be loaded
    And I click the "i.mdi-trash-can-outline" element
    And wait very long for the page to be loaded
    And I check "delete-docs"
    And I fill in the following:
      | course_code_delete | QP2 |
    And I click the "button.btn-danger" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see "QP1"
