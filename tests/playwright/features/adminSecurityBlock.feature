# Ported from tests/behat/features/adminSecurityBlock.feature.
#
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why (Selenium/Mink-only workaround; Playwright's click()
# already auto-scrolls into view, confirmed live for both links below with
# no zoom at all). Both links are still real, unchanged legacy pages.
Feature: Admin Security block navigation
  In order to verify admin security-related pages
  As a platform administrator
  I want to open security admin pages and ensure they load without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Login attempts
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Login attempts"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open File integrity
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "File integrity"
    And I wait for the page to be loaded
    Then I should not see an error
