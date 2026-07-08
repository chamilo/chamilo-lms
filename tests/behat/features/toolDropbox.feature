@common @tools
Feature: Dropbox tool
  In order to manage files in the course
  As a course administrator
  I want to open the Dropbox tool and access the upload dialog


  Background:
    Given I am a platform administrator


  Scenario: Admin opens Dropbox and sees the upload action
    Given I am on course "TEMP" homepage
    And I zoom out to maximum
    And I follow "Dropbox"
    And I wait for the page to be loaded
    Then I should see "Share a new file"
    And I click the "i.mdi-upload" element
    And I wait for the page to be loaded
    Then I should see "Upload"


