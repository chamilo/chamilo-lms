Feature: Session access
  In order to access a session
  The teacher must be registered as a session coach for this course

  @javascript
  Scenario: Create session 1
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Session1 |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    Then I should see "Add courses to this session (Session1)"
    Then I select "TEMP_PRIVATE (TEMPPRIVATE)" from "NoSessionCoursesList[]"
    And I press "add_course"
    And I press "next"
    Then I should see "Update successful"
    Then I should see "Subscribe users to this session"
    Then I follow "Multiple registration"
    Then wait for the page to be loaded
    Then I select "Yannick Warnier (ywarnier)" from "nosessionUsersList[]"
    And I press "add_user"
    And I press "next"
    Then I should see "Session1"
    Then I should see "TEMPPRIVATE"
    Then I should see "ywarnier"

  @javascript
  Scenario: Create session 2
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | Session2 |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    Then I should see "Add courses to this session (Session2)"
    Then I select "TEMP_PRIVATE (TEMPPRIVATE)" from "NoSessionCoursesList[]"
    And I press "add_course"
    And I press "next"
    Then I should see "Update successful"
    Then I should see "Subscribe users to this session"
    Then I follow "Multiple registration"
    Then wait for the page to be loaded
    Then I select "Michela Mosquera Guardamino (mmosquera)" from "nosessionUsersList[]"
    And I press "add_user"
    And I press "next"
    Then I should see "Session2"
    Then I should see "TEMPPRIVATE"
    Then I should see "mmosquera"

  Scenario: ywarnier connects to Session1
    Given I am logged as "ywarnier"
    Then I am on course "TEMPPRIVATE" homepage in session "Session1"
    Then I should not see "You are not allowed"

  Scenario: ywarnier connect to Session 2
    Given I am logged as "ywarnier"
    Then I am on course "TEMPPRIVATE" homepage in session "Session2"
    Then I should see "You are not allowed"

  Scenario: ywarnier connect to session that doesn't exists
    Given I am logged as "ywarnier"
    And I am on course "TEMPPRIVATE" homepage in session "Session does not exists"
    Then I should see "You are not allowed"

  Scenario: mmosquera connect to Session 1
    Given I am logged as "mmosquera"
    Then I am on course "TEMPPRIVATE" homepage in session "Session1"
    Then I should see "You are not allowed"

  Scenario: mmosquera connect to Session 2
    Given I am logged as "mmosquera"
    Then I am on course "TEMPPRIVATE" homepage in session "Session2"
    Then I should not see "You are not allowed"

  Scenario: Delete session "Session2"
    Given I am a platform administrator
    And I am on "/main/session/session_list.php?keyword=Session2"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then I should see "Deleted"

  Scenario: Delete session "Session1"
    Given I am a platform administrator
    And I am on "/main/session/session_list.php?keyword=Session1"
    And wait for the page to be loaded
    And I follow "Delete"
    And I confirm the popup
    Then I should see "Deleted"
