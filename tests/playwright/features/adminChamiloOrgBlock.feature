# Ported from tests/behat/features/adminChamiloOrgBlock.feature.
#
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why. "Open Chamilo extensions" was already commented out in
# the original Behat file (dead scenario) and is dropped here too. Every
# other link confirmed live to still be a real, followable link with no
# zoom needed.
#
# REAL CI FAILURE (not reproduced locally): "Open Chamilo official services
# providers" once failed almost instantly with Playwright's own "Target
# page, context or browser has been closed" thrown out of the test's own
# fixture setup (browser.newContext), before any of this scenario's steps
# even ran — i.e. the shared per-worker browser was already gone by the
# time this test started. Investigated for a code-level resource leak: this
# file's Background and every scenario use only the standard `page` fixture
# (one fresh context per test, closed automatically by Playwright) — no
# `context.newPage()`, `waitForEvent('popup')`, or manually-opened
# context/page anywhere in this file or in the "I follow"/"I am on"/"I wait
# for the page to be loaded"/"I should not see an error" steps it uses
# (common.steps.ts). The admin dashboard's Chamilo.org block links also have
# no `target="_blank"` (confirmed in AdminBlock.vue/IndexBlocksController.php),
# so "I follow" navigates the SAME page/tab rather than spawning a tab — ruling
# out a popup/tab leak as the cause. A full local re-run of all 9 scenarios in
# this file passed cleanly (9/9, ~2.4 min). Same signature already
# investigated and documented as CI-runner-specific Chromium instability (not
# a code defect) in toolGroup.feature's header comment and its "Create an
# announcement as acostea and send only to fapple" scenario — same
# conclusion applies here: no server error, no JS exception, likely transient
# resource exhaustion on the 2-worker CI runner, not a leak in this suite's
# code.
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
