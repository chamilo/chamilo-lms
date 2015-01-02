@course
Feature: Course creation
  In order to teacher a course
  As a teacher
  I need to create a course

  Background:
    Given there are following users:
      | username | email               | plain_password | enabled | groups   |
      | student  | student@example.com | student        | yes     | Students |
      | teacher  | teacher@example.com | teacher        | yes     | Teachers |

  @javascript
  Scenario: A teacher creates a course
    Given I am logged in teacher
    When I go to the chamilo_core_course_add page
    And I fill in "course[title]" with "test"
    And I press "course_save"
    Then I should see "Course created"

#  Scenario: A student creates a course
#    Given I am logged in student
#    When I add course "My course" as user "teacher"
#    Then I should not find a course "My course" in the portal
#
