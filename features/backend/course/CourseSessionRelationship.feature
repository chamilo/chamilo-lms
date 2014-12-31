@session
Feature: Course Session Relationship
  In order to setup a course session
  As a teacher
  I need a working relationship

  Background:
    Given there are following users:
      | username | email                 | plain_password  | enabled | groups |
      | student  | student@example.com | student          | yes      | students |
      | teacher  | teacher@example.com | teacher          | yes      | teachers |
      | coach    | coach@example.com    | coach            | yes      | teachers |
    Given I have a course "My course"
    Given I have a session "My session"

  Scenario: A session contains a course
    When I add session "My session" to course "My course"
    Then I should find a course "My course" in session "My session"

  Scenario: A course in a session contains a user
    When I add student "student" to course "My course" in session "My session"
    Then I should find a user "student" in course "My course" in session "My session"

  Scenario Outline: A course in a session contains a user
    When I add user with status "<status>" with username "<username>" in course "My course" in session "My session"
    Then I should find a user "<username>" in course "My course" in session "My session"

  Examples:
    | username  | status    |
    | student   | student   |
    | teacher   | teacher   |
    | coach     | coach      |
