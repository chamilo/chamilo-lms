# Ported from tests/behat/features/adminChamiloOrgBlock.feature.
#
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why. "Open Chamilo extensions" was already commented out in
# the original Behat file (dead scenario) and is dropped here too. Every
# other link confirmed live to still be a real, followable link with no
# zoom needed.
Feature: Admin Chamilo.org block navigation
  In order to verify Chamilo.org links present in the admin dashboard
  As a platform administrator
  I want to open each Chamilo.org related link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Chamilo homepage
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Chamilo homepage"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open User guides
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "User guides"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Chamilo forum
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Chamilo forum"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Installation guide
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Installation guide"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Changes in last version
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Changes in last version"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Contributors list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Contributors list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Security guide
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Security guide"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Optimization guide
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Optimization guide"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Chamilo official services providers
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Chamilo official services providers"
    And I wait for the page to be loaded
    Then I should not see an error
