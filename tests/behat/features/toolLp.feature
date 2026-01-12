Feature: LP tool
  In order to use the LP tool
  The teachers should be able to create LPs

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a LP category
    Given I am on "/main/lp/lp_controller.php?cid=1&action=add_lp_category"
    And I wait for the page to be loaded
    When I fill in the following:
      | name | LP category 1 |
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "LP category 1"
    Then I should not see an error

  Scenario: Create a LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=add_lp"
    And I wait for the page to be loaded
    When I fill in the following:
      | lp_name | LP 1 |
   #    And I select "LP category 1" from "category_id"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "LP 1"

  Scenario: Add document to LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded
    And I press "LP 1"
    And I wait for the page to be loaded
    And I follow "Edit"
    And I wait for the page to be loaded
    And I follow "Create a new document"
    And I wait for the page to be loaded
    When I fill in the following:
      | idTitle | Document 1 |
    And I fill in editor field "content_lp" with "Sample HTML text"
    And I press "submit_button"
    And wait very long for the page to be loaded
    Then I should see "Document 1"

  Scenario: Add an exercise to LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait very long for the page to be loaded
    And I press "LP 1"
    And I wait for the page to be loaded
    And I follow "Edit"
    And I wait for the page to be loaded
   And I follow "Exercise 1"
# Then I should see "Adding a test to the course"
# And I press "submit_button"
# Then I should see "Click on the [Learner view] button to see your learning path"
# And I should see "Exercise 1"

  Scenario: Enter LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded
    And I press "LP 1"
    And wait very long for the page to be loaded
    Then I should see "LP 1"
    And I should see "Document 1"
    And I should see "Exercise 1"

  Scenario: Check the PDF export in LP list if hide SCORM PDF link is false
    Given I am on "/admin/settings/course"
    And I wait for the page to be loaded
    And I check the "hide_scorm_pdf_link" radio button with "false" value
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/main/lp/lp_controller.php?cid=1&action=list&isStudentView=true"
    And I wait for the page to be loaded
    Then I should see an icon with title "Export to PDF"

  Scenario: Check the PDF export in LP list if hide SCORM PDF link is true
    Given I am on "/admin/settings/course"
    And I wait for the page to be loaded
    And I check the "hide_scorm_pdf_link" radio button with "true" value
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/main/lp/lp_controller.php?cid=1&action=list&isStudentView=true"
    And I wait for the page to be loaded
    Then I should not see an icon with title "Export to PDF"

  Scenario: LP exists and LP category exists
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    Then I should see "Learning path"
    Then I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded
    Then I should see "LP 1"
    And I should see "LP category 1"

  Scenario: Delete a LP
    Given I am not logged
    And I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded
    Then I should see "LP 1"
    And I click the "i.mdi-dots-vertical" element
    And I follow "Delete"
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should see "Deleted"
    And I should not see "LP 1"

  Scenario: Delete a LP category
    Given I am on "/main/lp/lp_controller.php?cid=1"
    And I wait for the page to be loaded
    Then I should see "LP category 1"
    And I follow "Assessments"
    And wait very long for the page to be loaded
    Then I click the "a.btn--action" element
    And I press "dropdown695d2c916d195"
    And I follow "Delete selected"
    And I should not see "LP category 1"
    And wait very long for the page to be loaded
