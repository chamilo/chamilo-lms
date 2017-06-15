Feature: Session tool
  In order to use the session tool
  The admin should be able to create a session

  Background:
    Given I am a platform administrator

  Scenario: Create a session category
    Given I am on "/main/session/session_category_add.php"
    When I fill in the following:
      | name | category_1 |
    And I press "Add category"
    Then I should see "The category has been added"

  Scenario: Create a session with hidden description
    Given I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Temp Session |
    And I press "advanced_params"
    And I fill in ckeditor field "description" with "Description for Temp Session"
    And I press "submit"
    Then I should see "Add courses to this session (Temp Session)"
    Then I select "TEMP (TEMP)" from "NoSessionCoursesList[]"
    And I press "add_course"
    And I press "next"
    Then I should see "Update successful"

  Scenario: Check hidden session description
    Given I am on "/user_portal.php?nosession=true"
    Then I should see "Temp Session"
    And I should not see "Description for Temp Session"

  Scenario: Show session description
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Edit"
    When I press "advanced_params"
    And I check "Show description"
    And I press "submit"
    Then I should see "Update successful"

  Scenario: Check shown session description
    Given I am on "/user_portal.php?nosession=true"
    Then I should see "Temp Session"
    And I should see "Description for Temp Session"

  Scenario: Delete session
    Given I am on "/main/session/session_list.php?keyword=Temp+session"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then I should see "Deleted"

  Scenario: Delete session category
    Given I am on "/main/session/session_category_list.php"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The selected categories have been deleted"