Feature: Validate JSON settings before saving
  Settings that expect JSON should display a syntax error without validating every keystroke.

  Scenario: A platform administrator cannot submit malformed JSON from the settings category page
    Given I am a platform administrator
    And I am on "/admin/settings/catalog"
    Then I should see the "textarea[data-json-setting='true']" element
    When I type "{" into the "textarea[data-json-setting='true']" element
    Then I should see "Invalid JSON syntax"
    When I press "Save settings"
    Then I should see "Invalid JSON syntax"
    And the URL should contain "/admin/settings/catalog"
    When I reload the current page
    Then I should not see "Invalid JSON syntax"

  Scenario: JSON validation also works in the settings search results
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=course_catalog_settings"
    Then I should see the "textarea[data-json-setting='true']" element
    When I type "{" into the "textarea[data-json-setting='true']" element
    Then I should see "Invalid JSON syntax"
    When I press "Save settings"
    Then I should see "Invalid JSON syntax"
    And the URL should contain "/admin/settings/search_settings"
