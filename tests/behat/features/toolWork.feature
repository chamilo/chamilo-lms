Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Scenario: Create a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I am on "/main/work/work.php?action=create_dir&cid=1"
    When I fill in the following:
      | new_dir | Work 1 |
    And I fill in editor field "description" with "Work description"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Directory created"

  Scenario: Edit a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I am on "/main/work/work.php?cid=1"
    And wait very long for the page to be loaded
    And I follow "Work 1"
    Then I should see "Work description"
    Then I follow "Edit"
    Then I should see "Assignment name"
    And wait for the page to be loaded
    And I press "Validate"
    And I wait for the page to be loaded
    Then I should see "Update successful"

  Scenario: Send work as student (acostea)
    Given I am not logged
    Given I am a student
    And I am on "/main/work/work.php?cid=1"
    And I wait for the page to be loaded
    Then I should see "Work 1"
    Then I follow "Work 1"
    Then I should see "Work 1"
    And I should see "Work description"
    Then I follow "Upload my assignment"
    Then I should see "Upload (Simple)"
    Then I follow "Upload (Simple)"
    And wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to "form-work_file"
    And I press "Upload"
    And wait for the page to be loaded
    Then I should see "The file has been added to the list of publications"

  Scenario: Check that work previously uploaded by student is available for the teacher.
    Given I am not logged
    Given I am a platform administrator
    And I am on "/main/work/work.php?cid=1"
    And wait for the page to be loaded
    And I follow "Work 1"
    And wait for the page to be loaded
    Then I should see "Work description"
    And wait for the page to be loaded
    Then I should see "favicon"

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
