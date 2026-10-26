@common
Feature: Social metadata settings use X branding
  To configure social metadata without breaking existing X cards
  As a platform administrator
  I want the tracking settings to use current X branding

  Scenario: Administrator sees X account labels in Tracking settings
    Given I am a platform administrator
    And I am on "/admin/settings/tracking"
    And I wait for the page to be loaded
    Then I should see "X Site account"
    And I should see "X Creator account"
    And I should not see "Twitter Site account"
    And I should not see "Twitter Creator account"
