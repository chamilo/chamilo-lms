@settings
Feature: Settings update
  In order to use Chamilo
  As an administrator
  I need to be able to update Chamilo settings

  # Ported from tests/behat/features/adminSettings.feature verbatim (the Behat
  # original has no teardown at all and just leaves these platform settings
  # permanently changed). The @settings tag on this Feature wires up a
  # BeforeAll/AfterAll pair in common.steps.ts that snapshots each setting's
  # actual current value once before any scenario here runs, and restores it
  # once after the last one finishes — see the comment above SETTINGS_PAGES
  # there for why this is a single before/after pair, not a per-scenario step.

  Scenario: Update 'profile' setting
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=changeable_options"
    And wait for the page to be loaded
    And I select "Name" from "form_changeable_options"
    And I additionally select "E-mail" from "form_changeable_options"
    And I additionally select "Official code" from "form_changeable_options"
    And I additionally select "Login" from "form_changeable_options"
    And I press "Save"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Update 'allow_registration' setting
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=allow_registration"
    And wait for the page to be loaded
    And I select "Yes" from "form_allow_registration"
    And I press "Save"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Update 'allow_group_categories' setting
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=allow_group_categories"
    And wait for the page to be loaded
    And I select "Yes" from "form_allow_group_categories"
    And I press "Save"
    And wait for the page to be loaded
    Then I should not see an error
