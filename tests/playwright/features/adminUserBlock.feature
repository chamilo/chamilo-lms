# Ported from tests/behat/features/adminUserBlock.feature.
#
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why. Every link confirmed live to still be a real, followable
# link with no zoom needed. Note: "Import users list" also appears in
# adminCourseBlock.feature's own block with the exact same text — same
# first-match ambiguity as adminSessionBlock.feature's "Resources
# sequencing" note; harmless here since both scenarios only assert "should
# not see an error".
Feature: Admin User management block
  In order to verify user management admin pages
  As a platform administrator
  I want to open each user management link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open User list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "User list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Add a user
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Add a user"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Export users list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Export users list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import users list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Import users list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Edit users list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Edit users list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Anonymise users list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Anonymise users list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Profiling
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Profiling"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Classes
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Classes"
    And I wait for the page to be loaded
    Then I should not see an error
