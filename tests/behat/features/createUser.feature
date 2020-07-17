@administration
Feature: Users management as admin
  In order to add users
  As an administrator
  I need to be able to create new users

  Background:
    Given I am a platform administrator

  Scenario: See the users list link on the admin page
    Given I am on "/main/admin/index.php"
    Then I should see "Users list"

  Scenario: See the user creation link on the admin page
    And I am on "/main/admin/index.php"
    Then I should see "Add a user"

  Scenario: Create a user with only basic info
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | Sammy                 |
      | lastname  | Marshall              |
      | email     | smarshall@example.com |
      | username  | smarshall             |
      | password  | smarshall             |
    And I check the "#send_mail_no" radio button selector
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: Create a user with wrong username
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | NIÑO                  |
      | lastname  | NIÑO                  |
      | email     | example@example.com |
      | username  | NIÑO                  |
      | password  | smarshall             |
    And I check the "#send_mail_no" radio button selector
    And I press "submit"
    Then I should see "Only letters and numbers allowed"

  Scenario: Create a user with wrong email
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | Juls                  |
      | lastname  | Juls                  |
      | email     | NI -ÑO@example.com      |
      | username  | Juls                  |
      | password  | Juls                  |
    And I check the "#send_mail_no" radio button selector
    And I press "submit"
    Then I should see "The email address is not complete or contains some invalid characters"

  Scenario: Search and delete a user
    And Admin top bar is disabled
    And I am on "/main/admin/user_list.php"
    And I fill in "keyword" with "smarshall"
    And I press "submit"
    When I follow "Delete"
    And I confirm the popup
    Then I should see "The user has been deleted"

  Scenario: Create a HRM user
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | HRM firstname|
      | lastname  | HRM lastname |
      | email     | hrm@example.com |
      | username  | hrm             |
      | password  | hrm             |
    And I check the "#send_mail_no" radio button selector
    And I fill in select bootstrap static input "#status_select" select "4"
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: Create a teacher user
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | teacher firstname|
      | lastname  | teacher lastname |
      | email     | teacher@example.com |
      | username  | teacher             |
      | password  | teacher             |
    And I fill in select bootstrap static input "#status_select" select "1"
    And I check the "#send_mail_no" radio button selector
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: Create a student user
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | student firstname|
      | lastname  | student lastname |
      | email     | student@example.com |
      | username  | student             |
      | password  | student             |
    And I fill in select bootstrap static input "#status_select" select "5"
    And I check the "#send_mail_no" radio button selector
    And I press "submit"
    Then I should see "The user has been added"

  Scenario: HRM follows teacher
    And I am on "/main/admin/user_list.php?keyword=hrm&submit=&_qf__search_simple="
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I follow "Assign users"
    And I select "teacher firstname teacher lastname" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    Then I should see "The assigned users have been updated"

  Scenario: HRM follows student
    And I am on "/main/admin/user_list.php?keyword=hrm&submit=&_qf__search_simple="
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I follow "Assign users"
    And I select "student firstname student lastname" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    Then I should see "The assigned users have been updated"

  Scenario: HRM logs as teacher
    Given I am logged as "hrm"
    And I am on "/main/mySpace/teachers.php"
    Then I should see "teacher lastname"
    Then I follow "teacher lastname"
    And wait for the page to be loaded
    And I follow "Login as"
    And wait for the page to be loaded
    Then I should see "Login successful"

  Scenario: HRM logs as student
    Given I am logged as "hrm"
    And I am on "/main/mySpace/student.php"
    Then I should see "student lastname"
    Then I follow "student lastname"
    And wait for the page to be loaded
    And I follow "Login as"
    And wait for the page to be loaded
    Then I should see "Login successful"
