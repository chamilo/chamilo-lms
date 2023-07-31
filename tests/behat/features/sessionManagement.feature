Feature: Session management tool
  In order to use the session tool
  The admin should be able to create a session

  Background:
    Given I am a platform administrator

  @javascript
  Scenario: Create a session category
    Given I am on "/main/session/session_category_add.php"
    When I fill in the following:
      | name | category_1 |
    And I press "Add category"
    And wait very long for the page to be loaded
    Then I should see "The category has been added"

  @javascript
  Scenario: Create a session
    Given I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Session1 |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Add courses to this session (Session1)"
    Then I select "TEMP (TEMP)" from "NoSessionCoursesList[]"
    And I press "add_course"
    And I press "next"
    Then I should see "Update successful"

  @javascript
  Scenario: Create a session with description
    Given I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Temp Session |
    And I press advanced settings
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And wait very long for the page to be loaded
    And I fill in ckeditor field "description" with "Description for Temp Session"
    And I press "submit"
    Then I should see "Add courses to this session (Temp Session)"
    Then I select "TEMP (TEMP)" from "NoSessionCoursesList[]"
    And I press "add_course"
    And I press "next"
    Then I should see "Update successful"

  Scenario: Check session description is not present
    Given I am on "/user_portal.php?nosession=true"
    Then I should see "Temp Session"
    And I should not see "Description for Temp Session"

  Scenario: Edit session description setting
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Edit"
    And I press advanced settings
    And I check "Show description"
    And I press "submit"
    Then I should see "Update successful"

  Scenario: Check session description with platform setting off
    Given I am on "/main/admin/settings.php?search_field=show_session_description&category=search_setting"
    And I check the "show_session_description" radio button with "false" value
    And I press "Save settings"
    Then I am on "/user_portal.php?nosession=true"
    Then I should see "Temp Session"
    And I should not see "Description for Temp Session"

  Scenario: Check session description with platform setting on
    Given I am on "/main/admin/settings.php?search_field=show_session_description&category=search_setting"
    And I check the "show_session_description" radio button with "true" value
    And I press "Save settings"
    Then I should see "Update successful"
    Then I am on "/user_portal.php?nosession=true"
    Then I should see "Temp Session"
    And I should see "Description for Temp Session"

  Scenario: Delete session
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then I should see "Deleted"

  Scenario: Delete session "Session1"
    Given I am on "/main/session/session_list.php?keyword=Session1"
    And wait very long for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then I should see "Deleted"

  Scenario: Delete session category
    Given I am on "/main/session/session_category_list.php"
    And I follow "Delete"
    And I confirm the popup
    And wait for the page to be loaded
    Then I should see "The selected categories have been deleted"
