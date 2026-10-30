# Ported from tests/behat/features/toolAgenda.feature, which filled the legacy
# add-event form (/main/calendar/agenda.php?action=add). That page now
# redirects to the Vue agenda (/resources/ccalendarevent), so both scenarios
# create the event there, and they also edit and delete it, leaving the
# database as they found it. The redirect itself is covered by
# agendaLegacyRedirects.feature.
#
# Start/End date are not filled: the PrimeVue DatePicker input is readonly,
# and the dialog defaults them to the current day, which the month view shows.
# A save waits up to 60 seconds for the event, as calendarEventComment.feature.
Feature: Agenda tool
  In order to use the Agenda tool
  As any user with an agenda
  I need to add, edit and delete events

  @long-scenario
  Scenario Outline: Create, edit and delete a personal event as <role>
    Given <login>
    And I am on "/resources/ccalendarevent"
    And I wait up to 60 seconds for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Personal event <role> |
    And I fill in tinymce field "calendar-event-content" with "Content of the personal event"
    And I press "Add"
    Then I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Personal event <role>')" to appear

    When I click the ".fc-event:has-text('ZZ Personal event <role>')" element
    Then I should see "Content of the personal event"

    When I press "Edit"
    And the field "Title" should have value "ZZ Personal event <role>"
    And I fill in "Title" with "ZZ Edited personal event <role>"
    And I press "Edit"
    Then I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Edited personal event <role>')" to appear

    When I click the ".fc-event:has-text('ZZ Edited personal event <role>')" element
    And I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Edited personal event <role>"

    Examples:
      | role          | login                         |
      | administrator | I am a platform administrator |
      | teacher       | I am a teacher                |
      | student       | I am a student                |

  @long-scenario
  Scenario: Create, edit and delete an event inside course TEMP
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"
    And I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Course agenda event |
    And I fill in tinymce field "calendar-event-content" with "Content of the course event"
    And I press "Add"
    Then I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Course agenda event')" to appear

    When I click the ".fc-event:has-text('ZZ Course agenda event')" element
    Then I should see "Content of the course event"
    And I should see "TEMP"

    When I press "Edit"
    And the field "Title" should have value "ZZ Course agenda event"
    And I fill in "Title" with "ZZ Edited course agenda event"
    And I press "Edit"
    Then I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Edited course agenda event')" to appear

    When I click the ".fc-event:has-text('ZZ Edited course agenda event')" element
    And I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Edited course agenda event"
