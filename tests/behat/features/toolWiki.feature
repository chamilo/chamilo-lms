@common @tools
Feature: Wiki tool
  In order to edit a wiki in a course
  As a course administrator
  I want to edit the wiki content and see it listed


  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded


  Scenario: Admin edits a wiki and sees the new content
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Wiki"
    And I wait for the page to be loaded
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in editor field "content" with "New Wiki"
    And I press "wiki_SaveWikiChange"
    And I wait for the page to be loaded
    Then I should see "New Wiki"
