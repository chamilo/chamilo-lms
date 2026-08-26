# Ported from tests/behat/features/toolAgenda.feature. All three legacy/Vue
# pages targeted are still live, confirmed via source reading:
# - agenda.php's add-event form fields (title, date_range, content) are
#   unchanged (public/main/inc/lib/agenda.lib.php).
# - "date_range" is a jQuery daterangepicker bound to a plain text input
#   (formvalidator/Element/DateRangePicker.php) — filling it with the exact
#   "YYYY-MM-DD HH:mm / YYYY-MM-DD HH:mm" string via the existing "I fill in
#   ... with ..." step is enough: .fill() dispatches a change event, and the
#   widget's own change handler is what actually populates the two hidden
#   date_range_start/date_range_end fields the form submits. Added a new
#   "I focus" step (matches the original's own pre-fill focus) but it isn't
#   strictly required for correctness, just parity with the original intent.
# - /resources/ccalendarevent (general/personal agenda) is a Vue page
#   (CCalendarEventList.vue/CCalendarEventCreate.vue/CCalendarEventForm.vue).
#   "Add event" itself is a real drift from the original assumption — it's
#   now a plain icon-only <button> (accessible name "Add event" via its
#   title attribute), not an <a> link — confirmed via a real local run
#   against a fresh install ("I follow" only matches links, so it hung
#   retrying for the full test timeout); fixed to "I press" instead.
#   "Title"/editor "Content" are resolvable as before, but Start/End date
#   (BaseCalendar.vue, PrimeVue's DatePicker) are NOT — same "I don't know
#   how to set the start/end date" limitation the ORIGINAL Behat scenario's
#   own comment already admitted (it just guessed field names "startDate"/
#   "endDate" that were never actually verified to work). Confirmed why:
#   the input is genuinely readonly (no manual typing possible, by design —
#   PrimeVue's own keyboard-input path never engages), and the popup's time
#   picker only exposes hour/minute increment/decrement buttons, no direct
#   entry — setting an arbitrary exact time would mean clicking those up to
#   dozens of times. Since the field already defaults to a sensible current
#   date/time when the dialog opens, this port just doesn't touch it at
#   all and relies on that default, matching the spirit of a smoke test
#   (event gets created; the exact scheduled time isn't what's under test).
#   "Add" itself needed a further pressButton() fix (see common.steps.ts) —
#   the same icon-left PrimeVue Button pattern responsible for "Save"'s
#   fix elsewhere in this file's sibling features also broke an exact
#   getByRole match here, and the page separately has an unrelated "Add
#   reminder" button, so the fallback substring match needed disambiguating
#   too, not just the exact one.
# - The two fully-commented-out scenarios in the original (inviting another
#   user to a personal event, with/without edit rights) are dropped, not
#   ported — same as every other already-ported feature's convention for
#   scenarios that were already inert in the original Behat suite.
Feature: Agenda tool
  In order to use the Agenda tool
  The admin should be able to add an event

  Background:
    Given I am a platform administrator

  Scenario: Create a personal event
    Given I am on "/main/calendar/agenda.php?action=add&type=personal"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Event 1 |
    And I focus "date_range"
    And I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"
    Then I fill in editor field "content" with "Description event"
    And I press "Add event"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create an event inside course TEMP
    Given I am on "/main/calendar/agenda.php?action=add&type=course&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Event in course |
    Then I fill in editor field "content" with "Description event"
    Then I wait for the page to be loaded
    And I focus "date_range"
    And I fill in "date_range" with "2017-03-07 12:15 / 2017-03-07 12:15"
    And I press "Add event"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a personal event from the general agenda
    Given I am on "/resources/ccalendarevent"
    When I press "Add event"
    Then I fill in the following:
      | Title | Personal event from general agenda |
    And I fill in tinymce field "calendar-event-content" with "Content for personal event from general agenda"
    And I press "Add"
    Then I should see "Personal event from general agenda"
