Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a work
    Given I am on "/main/work/work.php?action=create_dir&cidReq=TEMP"
    When I fill in the following:
      | new_dir | Work 1 |
    And I fill in ckeditor field "description" with "Work description"
    And I press "submit"
    Then I should see "Directory created"
