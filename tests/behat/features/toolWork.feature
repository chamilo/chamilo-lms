Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Scenario: Create a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    Then I click the "span.mdi-folder-plus" element
    And I wait for the page to be loaded
    When I fill in the following:
      | name | Work 1 |
    And I fill in editor field "description" with "Work description"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Assignment created"

  Scenario: Edit a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait very long for the page to be loaded
    And I follow "Work 1"
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

  Scenario: Send work as student (acostea)
    Given I am not logged
    Given I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And I wait for the page to be loaded
    Then I should see "Work 1"
    Then I follow "Work 1"
    And wait for the page to be loaded
    Then I should see "Work 1"
    And I should see "Work description"
    Then I follow "Upload file"
    And wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "form-work_file"
    And I press "Upload"
    And wait for the page to be loaded
    Then I should see "File uploaded successfully"

  Scenario: Check that work previously uploaded by student is available for the teacher.
    Given I am not logged
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded
    And I follow "Work 1"
    And wait for the page to be loaded
    Then I should see "Work description"
    And I should see "favicon"

#  Scenario: Add a comment and a attachment to the work previously uploaded by student
#    Given I am a platform administrator
#    And I am on "/main/work/work.php?cid=1"
#    And wait for the page to be loaded
#    And I follow "Work 1"
#    Then I should see "Work description"
#    And wait for the page to be loaded
#    Then I follow "Correct and rate"
#    Then I fill in editor field "comment" with "This is a comment"
#    Then I attach the file "web/css/base.css" to "attachment"
#    And I press "Send message"
#    Then I should see "You comment has been added"
#    And I should see "Update successful"
