# As in the legacy agenda form, a new course event can be repeated until a
# given day, and the whole series can be deleted at once ("Delete all items").
# The dates, the recipients of the copies and the deletion rules are covered by
# tests/CoreBundle/Api/CCalendarEventRepeatApiTest.php.
Feature: Repeated course calendar events
  In order to schedule a recurring course activity
  As a teacher
  I need to repeat a course event and delete the whole series

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: Create a daily course event, then delete the whole series
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Repeated event |
    And I check "repeat"
    And I select the dropdown option containing "Daily" in "calendar-event-repeat-type"
    And I fill in the date field "calendar-event-repeat-end-date" with the date 3 days from today
    And I press "Add"
    Then I should see at least 3 elements ".fc-event:has-text('ZZ Repeated event')"

    When I click the ".fc-event:has-text('ZZ Repeated event')" element
    And I press "Delete all items"
    And I press "Yes"
    Then I should see 0 elements ".fc-event:has-text('ZZ Repeated event')"

  Scenario: The personal agenda has no repeat option
    Given I am on "/resources/ccalendarevent"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    Then I should not see "Repeat event"
