@user
Feature: User login
  In order to have access to a course
  As a user
  I need to be able to login

  Background:
    Given there are following users:
      | username        | email                       |plain_password| enabled | group |
      | student_behat  | student_behat@example.com |student_behat|    yes    | students |

  @javascript
  Scenario Outline: Existing user can login
    Given I am on the login page
    When I fill in "_username" with "<username>"
    And I fill in "_password" with "<password>"
    And I press "_submit"
    Then I should see "<message>"

    Examples:
      | username        | password       | message |
      | student_behat  | student_behat  | Logout |
      | pirate          | pirate          | Bad credentials |
