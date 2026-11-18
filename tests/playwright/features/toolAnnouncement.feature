# Rewritten for the Vue announcement tool (AnnouncementListView/AnnouncementFormView):
# the legacy page public/main/announcements/announcements.php now redirects to the
# SPA, so the old selectors (#users dual listbox, "content" field, "choose_recipients",
# jqGrid) no longer exist. Confirmed live:
# - the form has a "Recipients" multi-select (default chip "Everyone"; choosing a
#   person replaces it), a "Subject" input (name="title") and a TinyMCE "Description"
#   with a generated instance id (hence the active-editor step);
# - "Preview" must be pressed before "Save" appears and lists who will receive it;
# - saving returns to the list; the header "Delete all" button (icon only, title
#   "Delete all") asks for confirmation through the PrimeVue ConfirmDialog ("Yes").
Feature: Announcement tool
  In order to use the Announcement tool
  The teachers should be able to create Announcements

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create an announcement for admin user
    When I follow the course tool "Announcements"
    And I follow "Add an announcement"
    Then I wait for the element "#announcement_recipients" to appear
    When I fill in the following:
      | title | Announcement test |
    And I choose "John Doe" in the announcement recipients
    And I fill in the active tinymce editor with "Announcement description"
    And I press "Preview"
    Then I should see "Announcement will be sent to"
    And I should see "John Doe"
    When I press "Save"
    Then I should see "Announcement test"
    And I should not see an error

  Scenario: Create an announcement for all users
    When I follow the course tool "Announcements"
    And I follow "Add an announcement"
    Then I wait for the element "#announcement_recipients" to appear
    When I fill in the following:
      | title | Announcement test |
    And I fill in the active tinymce editor with "Announcement description"
    And I press "Preview"
    Then I should see "Announcement will be sent to"
    When I press "Save"
    Then I should see "Announcement test"
    And I should not see an error

  Scenario: Delete all announcements
    When I follow the course tool "Announcements"
    And I wait for the element "table" to appear
    And I press "Delete all"
    And I press "Yes"
    Then I should see "There are no announcements."
