Feature: LP tool
  In order to use the LP tool
  The teachers should be able to create LPs

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a LP category
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=add_lp_category"
    When I fill in the following:
      | name | LP category 1 |
    And I press "submit"
    Then I should see "Added"

  Scenario: Create a LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=add_lp"
    When I fill in the following:
      | lp_name | LP 1 |
#    And I select "LP category 1" from "category_id"
    And I press "submit"
    Then I should see "Click on the [Learner view] button to see your learning path"

  Scenario: Add document to LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Edit learnpath"
    And I follow "Create a new document"
    When I fill in the following:
      | idTitle | Document 1 |
    And I fill in ckeditor field "content_lp" with "Sample HTML text"
    And I press "submit_button"
    Then I should see "Document 1"

  Scenario: Add an exercise to LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "Edit learnpath"
    And I follow "Tests"
    And I follow "Exercise 1 - Copy"
    And I should see "Adding a test to the course"
    When I fill in the following:
      | idTitle | Exercise 1 - Copy |
    And I press "submit_button"
    Then I should see "Click on the [Learner view] button to see your learning path"
    And I should see "Exercise 1 - Copy"

  Scenario: Enter LP
    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
    And I follow "LP 1"
    And wait for the page to be loaded
    Then I should see "LP 1"
    And I should see "Document 1"
    And I should see "Exercise 1 - Copy"

#  Scenario: Check the PDF export in LP list if hide SCORM PDF link is false
#    Given I am on "/main/admin/settings.php?category=Course"
#    And I check the "hide_scorm_pdf_link" radio button with "false" value
#    And I press "Save settings"
#    And I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list&isStudentView=true"
#    Then I should see an icon with title "Export to PDF"
#
#  Scenario: Check the PDF export in LP list if hide SCORM PDF link is true
#    Given I am on "/main/admin/settings.php?category=Course"
#    And I check the "hide_scorm_pdf_link" radio button with "true" value
#    And I press "Save settings"
#    And I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list&isStudentView=true"
#    Then I should not see an icon with title "Export to PDF"

#  Scenario: LP exists and LP category exists
#    Given I am on course "TEMP" homepage
#    Then I should see "Learning path"
#    Then I am on "/main/lp/lp_controller.php?cidReq=TEMP"
#    Then I should see "LP 1"
#    And I should see "LP category 1"

#  Scenario: Delete a LP
#    Given I am not logged
#    And I am a platform administrator
#    And I am on course "TEMP" homepage
#    And I am on "/main/lp/lp_controller.php?cidReq=TEMP&action=list"
#    Then I should see "LP category 1"
#    And I follow "Delete"
#    And I confirm the popup
#    Then I should not see "LP 1"
#
#  Scenario: Delete a LP category
#    Given I am on "/main/lp/lp_controller.php?cidReq=TEMP"
#    Then I should see "LP category 1"
#    And I follow "Delete"
#    Then I should see "Deleted"