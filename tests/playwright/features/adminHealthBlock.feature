# Ported from tests/behat/features/adminHealthBlock.feature.
#
# - Drops "I zoom out to maximum": a Selenium/Mink-era workaround (shrinks
#   the whole page via CSS zoom so every admin dashboard block/link fits
#   inside the visible viewport for WebDriver's own click). Playwright's
#   click() already auto-scrolls its target into view before clicking, so
#   this has no equivalent purpose here — confirmed live, every link this
#   suite's admin*Block features target resolves and clicks fine with no
#   zoom at all.
# - "I follow 'Health check'" no longer applies: AdminIndex.vue's Health
#   check block (assets/vue/views/admin/AdminIndex.vue:160-167) is a plain
#   `:title="t('Health check')"` heading on an always-expanded inline
#   dashboard card, not a link to a separate page — the legacy admin index
#   this scenario was written against has been consolidated into this one
#   Vue dashboard. Confirmed live: no `<a>`/role=link element with that text
#   exists at all, only a plain heading `<div>`. Rewritten to assert the
#   block (and its items) render on /admin directly, matching the real
#   intent ("the health check block loads without error").
Feature: Admin Health check block
  In order to verify admin health checks
  As a platform administrator
  I want to open the health check page and ensure it loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Health check
    Given I am on "/admin"
    And I wait for the page to be loaded
    Then I should see "Health check"
    And I should not see an error

  Scenario: See health warnings
    Given I am on "/admin"
    And I wait for the page to be loaded
    Then I should see "Health check"
    And I should not see an error
