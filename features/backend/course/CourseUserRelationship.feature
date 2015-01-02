@course
Feature: Course user relationship
  In order to setup add a user to a course
  As a teacher
  I need a working relationship

  Background:
    Given there are following users:
      | username | email               | plain_password | enabled | groups   |
      | student  | student@example.com | student        | yes     | Students |
      | teacher  | teacher@example.com | teacher        | yes     | Teachers |
    Given I have a course "My course"

  Scenario: A course contains a student
    When I add student "student" to course "My course"
    Then I should find a student "student" in course "My course"

  Scenario: A course contains a teacher
    When I add teacher "teacher" to course "My course"
    Then I should find a teacher "teacher" in course "My course"
