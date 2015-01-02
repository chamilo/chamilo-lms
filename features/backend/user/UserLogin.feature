@user
Feature: User login
  In order to have access to a course
  As a user
  I need to be able to login

  Background:
    Given there are following users:
      | username         | email                        | plain_password   | enabled | groups                  |
      | student          | student@example.com          | student          | yes     | Students                |
      | teacher          | teacher@example.com          | teacher          | yes     | Teachers                |
      | admin2           | admin2@example.com           | admin2           | yes     | Administrators          |
      | rrhh             | rrhh@example.com             | rrhh             | yes     | Human resources manager |
      | session_manager  | session_manager@example.com  | session_manager  | yes     | Session manager         |
      | question_manager | question_manager@example.com | question_manager | yes     | Question manager        |

  @javascript
  Scenario Outline: Existing user can login
    Given I am on the login page
    When I fill in "_username" with "<username>"
    And I fill in "_password" with "<password>"
    And I press "_submit"
    Then I should see "<message>"

    Examples:
      | username         | password         | message                 |
      | student          | student          | Hello, student          |
      | teacher          | teacher          | Hello, teacher          |
      | admin2           | admin2           | Hello, admin2           |
      | rrhh             | rrhh             | Hello, rrhh             |
      | session_manager  | session_manager  | Hello, session_manager  |
      | question_manager | question_manager | Hello, question_manager |
      | pirate           | pirate           | Bad credentials         |
