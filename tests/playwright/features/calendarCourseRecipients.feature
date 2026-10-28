# A course event can be sent to selected users and groups of the course
# (legacy "users_to_send"), and teachers can list only the events sent to one
# user or group. Who sees which event is covered by
# tests/CoreBundle/Api/CCalendarEventRecipientApiTest.php; this file covers the
# interface: the "Send to" selector when creating and editing an event, and
# the "Filter" selector above the agenda.
#
# The admin is the only user every install subscribes to TEMP, so the event is
# sent to the admin; the options are matched by the "(admin)" fragment because
# the full label carries the install's own first and last name.
Feature: Course calendar event recipients
  In order to send a course event only to the people concerned
  As a teacher
  I need to choose the recipients of a course event and filter the agenda by recipient

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: Send a course event to one user, filter by that user, then send it to the whole course
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element "#recipient_filter" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Recipient event |
    And I press the multiselect option containing "(admin)" in "course_recipients" inside the dialog
    And I press "Add"
    Then I should see "ZZ Recipient event"

    When I select the dropdown option containing "(admin)" in "recipient_filter"
    And I wait for the URL to contain "recipient=USER"
    Then I should see "ZZ Recipient event"

    When I click the ".fc-event:has-text('ZZ Recipient event')" element
    And I press "Edit"
    And I wait for the element ".p-dialog .p-multiselect-chip-item" to appear
    And I press the multiselect option containing "(admin)" in "course_recipients" inside the dialog
    And I press "Edit"
    # The event is now sent to the whole course, so the filter by user hides it.
    Then I should not see "ZZ Recipient event"

    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Recipient event')" to appear
    Then I should see "ZZ Recipient event"
    When I click the ".fc-event:has-text('ZZ Recipient event')" element
    And I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Recipient event"

  Scenario: The personal agenda has no recipient filter
    Given I am on "/resources/ccalendarevent"
    And I wait for the element ".fc-toolbar" to appear
    Then I should not see "Filter"
