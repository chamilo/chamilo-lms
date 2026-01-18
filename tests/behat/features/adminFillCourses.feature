Feature: Admin fill courses and subscribe users

  Background:
    Given I am logged as "admin"

  Scenario: Admin fills courses then subscribes a user to a course with long waits
    Given I am on "/main/admin/filler.php?fill=courses"
    When wait very long for the page to be loaded
    When I am on "/main/admin/subscribe_user2course.php"
    And wait very long for the page to be loaded
    And I select "ywarnier" from "UserList[]"
    And I select "(SOLARSYSTEM) Our solar system" from "CourseList[]"
    When I click the "button.btn--primary" element
    When wait very long for the page to be loaded
    Then I should see "The selected users are subscribed to the selected course"
