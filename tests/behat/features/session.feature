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

#  @javascript
#  Scenario: Create a session
#    Given I am on "/main/session/session_add.php"
#    When I fill in the following:
#      | name | Session 1 |
#    And I fill in select2 input "#coach_username" with id "1" and value "admin"
#    And I press "submit"
#    Then I should see "Add courses to this session (Session 1)"
#    Then I select "TEMP (TEMP)" from "NoSessionCoursesList[]"
#    And I press "add_course"
#    And I press "next"
#    Then I should see "Update successful"
#
#  Scenario: Delete session
#    Given I am on "/main/session/session_list.php?keyword=Session+1"
#    And wait for the page to be loaded
#    And I follow "Delete"
#    And I confirm the popup
#    Then I should see "Deleted"
#
  Scenario: Delete session category
    Given I am on "/main/session/session_category_list.php"
    And I follow "Delete"
    And I confirm the popup
    Then I should see "The selected categories have been deleted"