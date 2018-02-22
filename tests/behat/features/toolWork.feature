Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Scenario: Create a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I am on "/main/work/work.php?action=create_dir&cidReq=TEMP"
    When I fill in the following:
      | new_dir | Work 1 |
    And I fill in ckeditor field "description" with "Work description"
    And I press "submit"
    Then I should see "Directory created"

  Scenario: Send work as student
    Given I am a student
    And I am on "/main/work/work.php?cidReq=TEMP"
    And wait for the page to be loaded
    And I follow "Work 1"
    Then I should see "Work 1"
    Then I follow "Upload my assignment"
    Then I should see "Upload a document"
    Then I follow "Upload (Simple)"
    Then I should see "File extension"
    Then I attach the file "css/base.css" to "file"
    And I press "Upload"
    And wait for the page to be loaded
    Then I should see "The file has been added to the list of publications"
