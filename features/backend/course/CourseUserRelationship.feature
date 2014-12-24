Feature: Course User Relationship
  In order to setup a course subscription
  As a teacher
  I need a working relationship

  Background:
      Given there are following users:
        | username | email       | plain_password | enabled |
        | student  | student@example.com | student | yes     |
        | teacher  | teacher@example.com | teacher | yes     |
      Given I have a course "My course"

  Scenario: A course contains a user
      When I add student "student" to course "My course"
      Then I should find a user "student" in course "My course"

  Scenario: A course contains a user
      When I add teacher "teacher" to course "My course"
      Then I should find a user "teacher" in course "My course"
