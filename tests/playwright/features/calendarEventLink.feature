# A learning path item of type calendar_event (migrated from 1.11) opens the
# course agenda on the day of the event, in the day view, with the event shown
# (learnpath.class.php, buildVueCalendarEventLearningPathUrl). The link itself
# is covered by tests/CoreBundle/Legacy/LearnpathCalendarEventLinkTest.php;
# this file covers the agenda side: the date, view and event query parameters.
Feature: Link to a course calendar event
  In order to open a course event from a learning path
  As a course member
  I need the agenda to open on the day of the event with the event shown

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: A link opens the agenda on the event, which can then be deleted
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Linked event |
    And I press "Add"
    Then I should see "ZZ Linked event"

    When I open the event "ZZ Linked event" from a link to the agenda of course "TEMP"
    Then I wait up to 60 seconds for the element ".p-dialog:has-text('ZZ Linked event')" to appear
    And I wait for the element ".fc-timeGridDay-view" to appear

    When I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Linked event"

  Scenario: The agenda opens on the date and in the view of the link
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0&date=2040-01-15&view=timeGridDay" with the resolved id of course "TEMP"
    Then I wait for the element ".fc-timeGridDay-view" to appear
    And I wait for the element ".fc-toolbar-title:has-text('2040')" to appear
