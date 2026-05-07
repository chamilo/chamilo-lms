@administration
Feature: Users management as admin
  In order to add users
  As an administrator
  I need to be able to create new users

  Background:
    Given I am a platform administrator

  Scenario: See the users list link on the admin page
    Given I am on "/admin/user-list"
    And I dump browser console logs
    And I wait until I see "User list"
    Then I should see "User list"
    And I should see "Add a user"

  Scenario: Create a user with only basic info
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | Sammy                 |
      | lastname  | Marshall              |
      | email     | smarshall@example.com |
      | username  | smarshall             |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "smarshall"
    And I select "Learner" from "Roles"
    And I click the "input#send_mail_no" element
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a user with wrong username
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | NIÑO                  |
      | lastname  | NIÑO                  |
      | email     | example@example.com |
      | username  | NIÑO                  |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "smarshall"
    And I select "Learner" from "Roles"
    And I click the "input#send_mail_no" element
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should see "Only letters and numbers allowed"

  Scenario: Create a user with wrong email
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | Juls                  |
      | lastname  | Juls                  |
      | email     | NI -ÑO@example.com      |
      | username  | Juls                  |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "Juls"
    And I select "Learner" from "Roles"
    And I click the "input#send_mail_no" element
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should see "The email address is not complete or contains some invalid characters"


  Scenario: Search a user
    Given I am on "/admin/user-list?keyword=smarshall"
    And I wait until I see "Sammy"
    Then I should see "Sammy"
    And I should see "Marshall"


  Scenario: Delete a user
    Given I am on "/admin/user-list?keyword=smarshall"
    And I wait until I see "smarshall"
    And I click the "i.mdi-delete" element
    And I confirm the popup
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a HRM user
    Given I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | HRM firstname|
      | lastname  | HRM lastname |
      | email     | hrm@example.com |
      | username  | hrm             |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "hrm"
    And I click the "input#send_mail_no" element
    And I select "Human Resources Manager" from "Roles"
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a teacher user
    And I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | teacher firstname|
      | lastname  | teacher lastname |
      | email     | teacher@example.com |
      | username  | teacher  |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "teacher00!"
    And I select "Teacher" from "Roles"
    And I click the "input#send_mail_no" element
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a student user
    Given I am on "/main/admin/user_add.php"
    And I fill in the following:
      | firstname | student firstname|
      | lastname  | student lastname |
      | email     | student@example.com |
      | username  | student   |
    And I click the "input[name='password[password_auto]'][value='0']" element
    And I fill in "password" with "student00!"
    And I select "Learner" from "Roles"
    And I click the "input#send_mail_no" element
    And I click the "button[name=submit]" element
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM follows teacher
    Given I am on "/admin/user-list?keyword=hrm"
    And I wait until I see "HRM lastname"
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I click the ".mdi-account-child" element
    And wait the page to be loaded when ready
    And I select "teacher firstname teacher lastname" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM follows student
    Given I am on "/admin/user-list?keyword=hrm"
    And I wait until I see "HRM lastname"
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I click the ".mdi-account-child" element
    And wait the page to be loaded when ready
    And I select "student firstname student lastname" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM logs as teacher
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/teachers.php"
    And I wait until I see "teacher lastname"
    Then I should see "teacher lastname"
    Then I follow "teacher lastname"
    And wait for the page to be loaded
    And I follow "Login as"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM logs as student
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/student.php"
    And I wait until I see "student lastname"
    Then I should see "student lastname"
    Then I follow "student lastname"
    And wait for the page to be loaded
    And I follow "Login as"
    And wait very long for the page to be loaded
    Then I should not see an error
