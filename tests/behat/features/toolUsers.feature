@common @tools
Feature: Users tool
  In order to manage course users
  As a course administrator
  I want to search for users and unsubscribe them


  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded


  Scenario: Admin searches for 'amann' and unsubscribes the user
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Users"
    And I wait for the page to be loaded
    And I click the "i.mdi-account-plus" element
    And I fill in the following:
      | search_user_keyword | amann |
    And I click the "em.mdi-magnify" element
    And I click the "a.btn-small" element
    And I wait for the page to be loaded
    Then I should see "amann"
    And I follow "Unsubscribe"
    And I confirm the popup


  Scenario: Admin uses a specific tab then searches for 'ywarnier' and unsubscribes
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Users"
    And I wait for the page to be loaded
    And I click the "a#tabs_69662037e3281-1" element
    And I wait for the page to be loaded
    And I click the "i.mdi-account-plus" element
    And I fill in the following:
      | search_user_keyword | ywarnier |
    And I click the "em.mdi-magnify" element
    And I click the "a.btn-small" element
    And I wait for the page to be loaded
    Then I should see "ywarnier"
    And I follow "Unsubscribe"
    And I confirm the popup
