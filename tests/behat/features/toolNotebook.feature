@common @tools
Feature: Notebook tool
  In order to keep private notes in a course
  As a course member
  I want to manage only my own notebook entries

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin creates, edits and deletes a personal note
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Notebook"
    And I wait for the page to be loaded
    And I click the "a[title='Add']" element
    And I wait very long for the page to be loaded
    And I fill in "title" with "My first note"
    And I fill in editor field "Content" with "Initial note details"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "My first note"
    And I should see "Initial note details"

    When I click the "[data-type='notebook'] a[title='Edit']" element
    And I wait very long for the page to be loaded
    And I fill in "title" with "My updated note"
    And I fill in editor field "Content" with "Updated note details"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "My updated note"
    And I should see "Updated note details"

    When I click the "[data-type='notebook'] button[title='Delete']" element
    And I click the ".p-confirmdialog-accept" element
    And I wait for the page to be loaded
    Then I should not see "My updated note"
