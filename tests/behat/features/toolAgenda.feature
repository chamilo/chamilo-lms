Feature: Agenda tool
  In order to use the Agenda tool
  The admin should be able to add an event

  Background:
    Given I am a platform administrator

  Scenario: Create a personal event
    Given I am on "/main/calendar/agenda.php?&action=add&type=personal"
    When I fill in the following:
      | title | Event 1 |
    Then I fill in ckeditor field "content" with "Description event"
    Then wait for the page to be loaded
    And I focus "date_range"
    And I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"
    And I press "Add event"
    Then I should see "Event added"

