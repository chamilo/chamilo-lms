# Ported from tests/behat/features/adminSessionBlock.feature.
#
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why. Every link confirmed live to still be a real, followable
# link with no zoom needed. Note: "Resources sequencing" also appears in
# adminCourseBlock.feature's own dashboard block with the exact same text —
# "I follow" clicks whichever of the two matches first in the DOM (same
# first-match ambiguity Mink's own steps always had), but since both
# scenarios only assert "should not see an error", either target satisfies
# the assertion.
Feature: Admin Sessions management block
  In order to verify sessions management admin pages
  As a platform administrator
  I want to open each sessions management link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Training sessions list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Training sessions list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Add a training session
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Add a training session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Sessions categories list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Sessions categories list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import sessions list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Import sessions list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Import list of HR directors into sessions
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Import list of HR directors into sessions"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Export sessions list
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Export sessions list"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Copy from course in session to another session
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Copy from course in session to another session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Move users results from base course to a session
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Move users results from base course to a session"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Careers and promotions
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Careers and promotions"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Manage session fields
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Manage session fields"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Resources sequencing (sessions)
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Resources sequencing"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Export all results from an exercise
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Export all results from an exercise"
    And I wait for the page to be loaded
    Then I should not see an error
