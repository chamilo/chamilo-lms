# Not ported — new. Seeds platform settings that other feature files depend
# on being correct for their ENTIRE run, run once as its own CI step before
# the main parallel "Playwright tests" batch — same reasoning and pattern as
# "Seed test course"/"Seed private course" in course.feature (see
# .github/workflows/playwright.yml).
#
# Real, confirmed root cause this fixes: GroupSettingsSchema.php's schema
# default for 'allow_group_categories' is 'false'. toolGroup.feature's entire
# category/group listing (public/main/group/group.php: `if ('true' ===
# api_get_setting('allow_group_categories'))`) depends on it being 'true' for
# every scenario in that file, for the file's whole duration. adminSettings.
# feature's "Update 'allow_group_categories' setting" scenario DOES set it to
# 'Yes', but that Feature is tagged @settings — its BeforeAll/AfterAll pair
# (common.steps.ts) snapshots the value once and restores it back to
# whatever it was BEFORE that Feature's own scenarios ran, i.e. back to
# 'false' on a fresh install, once its own scenarios finish. Since
# `fullyParallel: false` only serializes scenarios WITHIN one file — DIFFERENT
# files still run concurrently across workers — toolGroup.feature (a
# different file, different worker) would only see 'true' during the narrow
# window while adminSettings.feature's own scenario had it temporarily
# flipped, a pure worker-scheduling race. Confirmed via a real CI run:
# toolGroup.feature's very first scenario ("Create a group directory") saw
# genuinely zero categories rendered, no server error, right after
# successfully creating one — group.php's own `if (empty($categories))`
# fallback (auto-creates "Default groups") didn't even fire, meaning the
# whole `if ('true' === ...)` block was skipped entirely, i.e. the setting
# read as 'false' at that exact moment.
#
# Setting it here, once, before the main batch starts, makes adminSettings.
# feature's own BeforeAll snapshot ALSO see 'true' as the "current" value —
# so its AfterAll correctly restores back to 'true', not 'false' — closing
# the race for the whole run's duration, not just working around one file.
Feature: Seed platform settings
  In order to run the test suite reliably
  As CI
  I need certain platform settings pre-configured before other feature files run

  Scenario: Enable group categories before testing
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=allow_group_categories"
    And wait for the page to be loaded
    And I select "Yes" from "form_allow_group_categories"
    And I press "Save"
    And wait for the page to be loaded
    Then I should not see an error
