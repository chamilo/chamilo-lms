@common @admin @gh3793
Feature: Manage portable color themes
  Administrators can import, export, rename and safely delete unused color themes.

  Scenario: Import a portable theme, rename it, download it, and delete it
    Given I am a platform administrator
    And I am on "/admin/configuration/colors"
    When I wait for the element "input[name='importThemeName']" to appear
    And I fill in "importThemeName" with a unique value prefixed "GH3793 theme"
    And I attach the file "tests/playwright/fixtures/gh3793-color-theme.zip" to "themeArchive"
    And I click the "button[name='gh3793-import']" element
    Then I should see "Color theme imported"
    When I fill in "themeName" with "GH3793 Renamed"
    And I click the "button[name='gh3793-rename']" element
    Then I should see "Theme renamed"
    When I download the file from the link "Export theme ZIP"
    Then the downloaded file should contain "theme.json"
    When I click the "button[name='gh3793-delete']" element
    And I press "Yes"
    Then I should see "Theme deleted"

  Scenario: A student cannot access color-theme management
    Given I am a student
    When I am on "/admin/configuration/colors"
    Then I should not see "Import color theme ZIP"
