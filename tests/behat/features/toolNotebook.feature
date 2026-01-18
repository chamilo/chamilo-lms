@common @tools
Feature: Notebook tool
  In order to keep notes in a course
  As a course administrator
  I want to create a note and see it listed


  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded


  Scenario: Admin creates a note and sees it
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Notebook"
    And I wait for the page to be loaded
    And I click the "i.mdi-plus-box" element
    And wait very long for the page to be loaded
    And I fill in the following:
      | note_title | My first note |
    And I fill in editor field "Note details" with "test"
    And I press "note_SubmitNote"
    And I wait for the page to be loaded
    Then I should see "My first note"


