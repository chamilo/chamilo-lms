@settings-fontResize @font-resize
Feature: Font resizing across Chamilo text components
  In order to make the interface readable without browser zoom
  As a user with low vision
  I need the A+ control to scale text consistently and the setting to remain optional

  @long-scenario
  Scenario: Increasing and resetting text size works in the main administration and course screens
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=accessibility_font_resize"
    When I set the accessibility font resize setting to "true"
    And I press "Save settings"
    And I am on "/admin"
    Then the accessibility control should increase and reset font size for ".admin-index .p-menu-item-label"
    And I am on "/admin/course-list"
    Then the accessibility control should increase and reset font size for ".p-datatable-tbody tr td"
    And I am on "/admin/user-list"
    Then the accessibility control should increase and reset font size for ".p-datatable-tbody tr td"
    And I am on "/admin/session-list"
    Then the accessibility control should increase and reset font size for ".p-datatable-thead tr th"
    And I am on "/admin/settings/search_settings?keyword=accessibility_font_resize"
    Then the accessibility control should increase and reset font size for "form:has(#form_accessibility_font_resize) h4.text-body-1"
    And I am on course "TEMP" homepage
    Then the accessibility control should increase and reset font size for ".course-tool__title"
    And I follow the course tool "Learning paths"
    Then the accessibility control should increase and reset font size for ".lp-list .section-header__title"
    And I am on course "TEMP" homepage
    And I follow the course tool "Agenda"
    Then the accessibility control should increase and reset font size for ".calendar-agenda .fc-toolbar-title"

  @font-resize-roles
  Scenario: Font resizing remains available to teachers and students
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=accessibility_font_resize"
    When I set the accessibility font resize setting to "true"
    And I press "Save settings"
    And I am a teacher
    And I am on "/"
    Then the accessibility control should increase and reset font size for "body"
    And I am a student
    And I am on "/"
    Then the accessibility control should increase and reset font size for "body"

  Scenario: Turning off font resizing hides its control
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=accessibility_font_resize"
    When I set the accessibility font resize setting to "false"
    And I press "Save settings"
    And I am on "/admin"
    Then the accessibility font resize control should not be available
