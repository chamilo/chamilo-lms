# A course event has a comment, as in the legacy agenda form. The teacher
# writes it when creating or editing the event; the agenda shows it in the
# event dialog and in the list view. Who can read it is covered by
# tests/CoreBundle/Api/CCalendarEventCommentApiTest.php.
Feature: Course calendar event comment
  In order to add practical notes to a course event
  As a teacher
  I need to write a comment on the event and see it in the agenda

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: Create, read, edit and delete a course event with a comment
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title   | ZZ Comment event   |
      | comment | Bring the lab coat |
    And I press "Add"
    Then I should see "ZZ Comment event"

    When I click the ".fc-event:has-text('ZZ Comment event')" element
    Then I should see "Bring the lab coat"

    When I press "Edit"
    And the field "comment" should have value "Bring the lab coat"
    And I fill in "comment" with "Bring the safety glasses"
    And I press "Edit"
    Then I should see "ZZ Comment event"

    When I am on "/resources/ccalendarevent/list?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ":text('ZZ Comment event')" to appear
    Then I should see "Bring the safety glasses"
    And I should not see "Bring the lab coat"

    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Comment event')" to appear
    And I click the ".fc-event:has-text('ZZ Comment event')" element
    And I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Comment event"

  Scenario: The personal agenda has no comment field
    Given I am on "/resources/ccalendarevent"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    Then I should not see "Comment"
