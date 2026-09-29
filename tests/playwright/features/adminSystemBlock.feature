# Ported from tests/behat/features/adminSystemBlock.feature.
#
# - Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
#   comment for why.
# - "Open Update session status" dropped entirely: its whole entry is
#   commented out in IndexBlocksController (src/CoreBundle/Controller/Admin/
#   IndexBlocksController.php, "Disabled until it is reemplemented to work
#   with Chamilo 2") — a genuinely dead feature, confirmed live (no matching
#   link at all), not a rename/hidden item.
Feature: Admin System block navigation
  In order to verify admin system-related pages
  As a platform administrator
  I want to open system admin pages and ensure they load without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Clean temporary files
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Clean temporary files"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Special exports
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Special exports"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open System status
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "System status"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Data filler
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Data filler"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open E-mail tester
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "E-mail tester"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Tickets
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Tickets"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Colors
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Colors"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open File info
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "File info"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Resources by type
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Resources by type"
    And I wait for the page to be loaded
    Then I should not see an error
