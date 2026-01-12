Feature: Session management tool
  In order to use the session tool
  The admin should be able to create a session

  Background:
    Given I am a platform administrator

  Scenario: Create a session category
    Given I am on "/main/session/session_category_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | name | category_1 |
    And I press "Add category"
    And I wait for the page to be loaded
    Then I should see "category_1"
    And I should not see an error

  @javascript
  Scenario: Create a session
    Given I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Session 1 |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session (Session 1)"
    When I fill in ajax select2 input "#courses" with id "1" and value "TEMP"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Session 1"
    And I should not see an error

  @javascript
  Scenario: Create a session with description
    Given I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Temp Session |
    And I press "advanced_params"
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I wait for the page to be loaded
    And I fill in editor field "description" with "Description for Temp Session"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session (Temp Session)"
    When I fill in ajax select2 input "#courses" with id "1" and value "TEMP"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Check session description is not present
    Given I am on "/sessions"
    And I wait for the page to be loaded
    Then I should see "Temp Session"
    And I should not see "Description for Temp Session"

  Scenario: Edit session description setting
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And I wait for the page to be loaded
    When I follow "Temp Session"
    And I wait for the page to be loaded
    And I click the "i.mdi-pencil" element
    And I wait for the page to be loaded
    And I press "advanced_params"
    And I check "Show description"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should not see an error
    Then I should see "Temp Session"


  Scenario: Check session description with platform setting off
    Given I am on "/admin/settings/search_settings?keyword=show_session_description"
    And I wait for the page to be loaded
    When I select "No" from "form_show_session_description"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Show session description"
    And I should see "No"
    And I should not see an error
    Then I am on "/sessions"
    And I wait for the page to be loaded
    Then I should see "Temp Session"
    And I should not see "Description for Temp Session"

  Scenario: Check session description with platform setting on
    Given I am on "/admin/settings/search_settings?keyword=show_session_description"
    And I wait for the page to be loaded
    When I select "Yes" from "form_show_session_description"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should see "Show session description"
    And I should see "Yes"
    And I should not see an error
    Then I am on "/sessions"
    And I wait for the page to be loaded
    Then I should see "Temp Session"
    And I should see "Description for Temp Session"

  Scenario: Delete session
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And I wait for the page to be loaded
    When I click the "i.mdi-delete" element
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see "Temp session"
    And I should not see an error

  Scenario: Delete session "Session 1"
    Given I am on "/main/session/session_list.php?keyword=Session+1"
    And I wait for the page to be loaded
    When I click the "i.mdi-delete" element
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see "Session 1"
    And I should not see an error

  Scenario: Delete session category
    Given I am on "/main/session/session_category_list.php"
    And I wait for the page to be loaded
    When I click the "i.mdi-delete" element
    And I confirm the popup
    And I wait for the page to be loaded
    Then I should not see "category_1"
    And I should not see an error
