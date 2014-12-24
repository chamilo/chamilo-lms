Feature: Course Session Relationship
  In order to setup a course session
  As a teacher
  I need a working relationship

  Background:
    Given there are following users:
      | username | email       | plain_password | enabled |
      | student  | student@example.com | student | yes     |
      | teacher  | teacher@example.com | teacher | yes     |
    Given I have a course "My course"
    Given I have a session "My session"

  Scenario: A course contains a user
    When I add student "student" to course "My course"
    Then I should find a user "student" in course "My course"

  Scenario: A session contains a course
    When I add session "My session" to course "My course"
    Then I should find a course "My course" in session "My session"
