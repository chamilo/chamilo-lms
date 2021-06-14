Feature: Session management tool
  In order to use the session tool
  The admin should be able to create a session

  Background:
    Given I am a platform administrator

  Scenario: Create a session category
    Given I am on "/main/session/session_category_add.php"
    When I fill in the following:
      | name | category_1 |
    And I press "Add category"
    Then wait very long for the page to be loaded
    Then I should see "The category has been added"

  @javascript
  Scenario: Create a session
    Given I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Session1 |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    Then wait for the page to be loaded
    Then I should see "Add courses to this session (Session1)"
    Then I fill in ajax select2 input "#courses" with id "2" and value "TEMP"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Update successful"

  @javascript
  Scenario: Create a session with description
    Given I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Temp Session |
    And I press "advanced_params"
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And wait for the page to be loaded
    And I fill in editor field "description" with "Description for Temp Session"
    And I press "submit"
    Then wait for the page to be loaded
    Then I should see "Add courses to this session (Temp Session)"
    Then I fill in ajax select2 input "#courses" with id "2" and value "TEMP"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Update successful"

#  Scenario: Check session description is not present
#    Given I am on "/main/index/user_portal.php"
#    Then I should see "Temp Session"
#    And I should not see "Description for Temp Session"

  Scenario: Edit session description setting
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Temp Session"
    And I follow "Edit"
    And wait for the page to be loaded
    When I press "advanced_params"
    And I check "Show description"
    And I press "submit"
    Then wait very long for the page to be loaded
    Then I should not see an error
    #Then I should see "Update successful"

  Scenario: Check session description with platform setting off
    Given I am on "/admin/settings/search_settings?keyword=show_session_description"
    And I select "No" from "form_show_session_description"
    And I press "Save settings"
    Then wait very long for the page to be loaded
    Then I should see "Settings have been successfully updated"
#    Then I am on "/main/index/user_portal.php"
#    Then I should see "Temp Session"
#    And I should not see "Description for Temp Session"

  Scenario: Check session description with platform setting on
    Given I am on "/admin/settings/search_settings?keyword=show_session_description"
    And I select "Yes" from "form_show_session_description"
    And I press "Save settings"
    Then wait very long for the page to be loaded
    Then I should see "Settings have been successfully updated"
#    Then I am on "/main/index/user_portal.php"
#    Then I should see "Temp Session"
#    And I should see "Description for Temp Session"

  Scenario: Delete session
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then wait very long for the page to be loaded
    Then I should see "Deleted"

  Scenario: Delete session "Session1"
    Given I am on "/main/session/session_list.php?keyword=Session1"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then wait very long for the page to be loaded
    Then I should see "Deleted"

  Scenario: Delete session category
    Given I am on "/main/session/session_category_list.php"
    And I follow "Delete"
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should see "The selected categories have been deleted"
