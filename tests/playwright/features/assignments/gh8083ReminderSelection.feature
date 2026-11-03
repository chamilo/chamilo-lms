@common @assignments @gh8083
Feature: Send assignment reminders to selected students
  A teacher must be able to remind a subset of non-submitters without messaging everyone.

  Scenario: Remind two students without messaging the third
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I open the GH8083 assignment reminder page with mock recipients
    Then the GH8083 selected-reminder button is disabled
    When I select GH8083 reminder recipients "Student One" and "Student Three"
    And I confirm the GH8083 selected reminder
    Then the GH8083 reminder request contains only student IDs "80831,80833"

  Scenario: The existing send-to-all button remains available
    Given I am a teacher
    And I am on course "TEMP" homepage
    And I open the GH8083 assignment reminder page with mock recipients
    When I send the GH8083 reminder to all
    Then the GH8083 reminder request contains no user IDs
