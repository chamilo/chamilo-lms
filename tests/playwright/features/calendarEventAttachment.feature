# As in the legacy agenda form, a course event can have attached files. The
# teacher adds them when creating or editing the event and can delete them;
# the event dialog lists them as download links. Who can download or change
# them is covered by tests/CoreBundle/Api/CCalendarEventAttachmentApiTest.php.
Feature: Course calendar event attachments
  In order to share documents about a course event
  As a teacher
  I need to attach files to the event and delete them

  Background:
    Given I am a platform administrator
    And I resolve the numeric id of course "TEMP"

  @long-scenario
  Scenario: Create a course event with a file, then delete the file and the event
    Given I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    And I fill in the following:
      | Title | ZZ Event with file |
    And I attach the file "tests/CoreBundle/fixtures/logo.png" to "attachments"
    And I fill in "file_comment" with "The poster"
    And I press "Add"
    Then I should see "ZZ Event with file"

    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Event with file')" to appear
    And I click the ".fc-event:has-text('ZZ Event with file')" element
    Then I wait for the element ".p-dialog a:has-text('logo.png')" to appear
    And I should see "The poster"

    When I press "Edit"
    And I wait for the element ".p-dialog li:has-text('logo.png')" to appear
    And I click the ".p-dialog li:has-text('logo.png') button" element
    And I press "Yes"
    Then I should not see "logo.png"

    When I am on "/resources/ccalendarevent?cid=COURSE_ID&sid=0&gid=0" with the resolved id of course "TEMP"
    And I wait up to 60 seconds for the element ".fc-event:has-text('ZZ Event with file')" to appear
    And I click the ".fc-event:has-text('ZZ Event with file')" element
    Then I should not see "logo.png"
    When I press "Delete"
    And I press "Yes"
    Then I should not see "ZZ Event with file"

  Scenario: The personal agenda has no attachment field
    Given I am on "/resources/ccalendarevent"
    And I wait for the element ".fc-toolbar" to appear
    When I press "Add event"
    Then I should not see "Add attachment"
