# As in the legacy agenda form, a teacher can publish a new course event as a
# course announcement, sent by email to the same recipients. The agenda calls
# the announcement API, which owns the permission and recipient checks.
Feature: Course calendar event published as an announcement
  In order to warn learners about a new course event
  As a teacher
  I need to publish the event as a course announcement when I create it

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: Create a course event as an announcement, then delete both
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Announced event |
    And I check "add_announcement"
    And I press "Add"
    Then I should see "Additionally, a new announcement has been created and sent to selected users"
    And I should see "ZZ Announced event"

    When I am on course "TEMP" homepage
    And I follow the course tool "Announcements"
    And I wait up to 60 seconds for the element "tr:has-text('ZZ Announced event')" to appear
    And I click the "tr:has-text('ZZ Announced event') button[aria-label='Delete']" element
    And I press "Yes"
    Then I should not see "ZZ Announced event"

    # Only a new event can be published as an announcement, as in the legacy form.
    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Announced event')" to appear
    And I click the ".fc-event:has-text('ZZ Announced event')" element
    And I press "Edit"
    And the field "Title" should have value "ZZ Announced event"
    Then I should not see "Add an announcement"

    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Announced event')" to appear
    And I click the ".fc-event:has-text('ZZ Announced event')" element
    And I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Announced event"

  Scenario: The personal agenda has no announcement option
    Given I am on "/resources/ccalendarevent"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    Then I should not see "Add an announcement"
