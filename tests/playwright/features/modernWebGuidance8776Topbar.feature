@modern-web-guidance-8776 @mwg8776-topbar
Feature: Keyboard reachability of the mobile topbar
  The 320-pixel reflow check alone cannot establish that navigation actions are visible
  These checks do not send forms, change settings, or navigate through topbar actions

  @mwg8776-topbar-reachability
  Scenario Outline: Topbar links remain visible when focused at 320 CSS pixels
    Given I am a platform administrator
    When I inspect MWG8776 topbar at 320 pixels on "<path>"
    Then the MWG8776 focusable topbar controls must remain visually reachable

    Examples:
      | path                  |
      | /resources/usergroups |
      | /social/search        |

  @mwg8776-topbar-avatar
  Scenario: The user menu trigger has keyboard semantics and can open the menu
    Given I am a platform administrator
    When I inspect MWG8776 topbar at 320 pixels on "/social/search"
    Then the MWG8776 user menu trigger must work with the keyboard
