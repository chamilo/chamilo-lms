# The topbar "Tickets" icon must only be offered to users whose role is
# allowed in the default ticket project (project 1) by the
# `ticket_project_user_roles` platform setting — the condition the legacy
# header (template.lib.php, TicketManager::userIsAllowInProject(1)) applied
# and the Vue topbar lost in the migration, showing the icon to every
# logged-in user. Platform admins are always allowed.
#
# Legacy parity on purpose: only the icon is hidden. A user outside the
# configured roles can still open /tickets directly and see their own
# tickets, exactly as legacy tickets.php allowed — the last student scenario
# pins that so nobody "fixes" it into a hard block by accident.
#
# Both settings are global, so the first scenario sets them and the last one
# puts them back to their schema defaults (show_link_ticket_notification=No,
# ticket_project_user_roles empty — a genuinely blank cell, see the note in
# specialCase1PlatformSettings.feature about why `""` must not be used).
# Scenarios in one file run in order (fullyParallel: false).
#
# Selectors: the icon is targeted by its href rather than its translated
# title. The Inbox icon (allow_message_tool defaults to Yes) is waited for
# first in every check: both icons come from the same platform-config
# response, so its presence proves the topbar has its settings and the
# "should not see" assertion cannot pass merely because nothing rendered yet.
Feature: Ticket icon in the topbar
  In order to only offer the ticket tool to the roles allowed to use it
  As a platform administrator
  I want the topbar ticket icon to follow the ticket project role setting

  Scenario: Allow only teachers in the default ticket project
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=show_link_ticket_notification"
    And I wait for the page to be loaded
    And I select "Yes" from "form_show_link_ticket_notification"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=ticket_project_user_roles"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_ticket_project_user_roles | {"permissions":{"1":[1]}} |
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: A platform administrator always sees the ticket icon
    Given I am a platform administrator
    And I am on "/home"
    And I wait for the element "a.item-button[href^='/resources/messages']" to appear
    Then I should see the "a.item-button[href^='/tickets']" element

  Scenario: A teacher, whose role is allowed, sees the ticket icon
    Given I am a teacher
    And I am on "/home"
    And I wait for the element "a.item-button[href^='/resources/messages']" to appear
    Then I should see the "a.item-button[href^='/tickets']" element

  Scenario: A student, whose role is not allowed, does not see the ticket icon
    Given I am a student
    And I am on "/home"
    And I wait for the element "a.item-button[href^='/resources/messages']" to appear
    Then I should not see the "a.item-button[href^='/tickets']" element

  Scenario: A student can still reach their own tickets by URL, as in legacy
    Given I am a student
    And I am on "/tickets?project_id=1"
    And I wait for the page to be loaded
    Then I should see "Ticket number"
    And I should not see an error

  Scenario: Restore the ticket settings to their defaults
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=show_link_ticket_notification"
    And I wait for the page to be loaded
    And I select "No" from "form_show_link_ticket_notification"
    And I press "Save settings"
    And I wait for the page to be loaded
    And I am on "/admin/settings/search_settings?keyword=ticket_project_user_roles"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_ticket_project_user_roles |  |
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error
