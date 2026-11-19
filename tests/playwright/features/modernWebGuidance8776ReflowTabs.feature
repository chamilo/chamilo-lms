@modern-web-guidance-8776 @mwg8776-batch3
Feature: Social navigation semantics and narrow-viewport reflow
  Accessible controls and reflow must be verified in Chromium before changing production code
  These checks only navigate, move focus, and inspect the page; they never submit data

  @mwg8776-group-tabs
  Scenario: Social group tabs expose their selection and support keyboard navigation
    Given I am a platform administrator
    And I am on "/resources/usergroups"
    Then the MWG8776 social group tabs must expose accessible tab semantics and keyboard operation

  @mwg8776-narrow-reflow
  Scenario Outline: Social interfaces avoid document horizontal overflow at 320 CSS pixels
    Given I am a platform administrator
    When I inspect MWG8776 narrow reflow on "<path>"
    Then the MWG8776 page must reflow without horizontal document overflow

    Examples:
      | path                  |
      | /resources/usergroups |
      | /social/search        |
