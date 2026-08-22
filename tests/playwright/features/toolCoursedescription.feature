# Ported from tests/behat/features/toolCoursedescription.feature — rewritten,
# not verbatim. The Course description tool has been migrated to Vue
# (CourseDescriptionListView.vue/CourseDescriptionFormView.vue, /resources/
# course-description/...) since the original scenario was written, but most
# of its own step text still happens to match live reality:
# - The "image-text" icon still exists — CourseDescriptionListView.vue's
#   toolbar renders one icon-only BaseButton per description type, and type
#   1 ("Description", backend key TYPE_DESCRIPTION in
#   CourseDescriptionListProvider.php) uses icon "image-text". It's now a
#   toolbar button rather than a standalone page action, but confirmed live
#   the selector needs updating: same as toolWiki.feature's "Edit page"
#   icon, BaseIcon renders it as `<span class="mdi mdi-image-text">`, not a
#   bare `<i>` — "span.mdi-image-text" (not "i.mdi-image-text") is what
#   actually matches (confirmed live: count 1, "i.mdi-image-text" is 0).
# - "course_description_title" is a plain BaseInputText (id="course_description_title")
#   on this box, not a TinyMCE field — the live-verified `settings.saveTitlesAsHtml`
#   platform setting is off, so the existing plain "I fill in ... with ..." step
#   works unchanged (BaseTinyEditor only replaces it when that setting is on).
# - "I press "save"" still resolves correctly: the form's real Save button
#   carries `name="save"` (CourseDescriptionFormView.vue's BaseButton), and
#   pressButton() checks `[name="save"]` before falling back to visible text.
# One real rewrite: "I fill in editor field ... with ..." (window.
# setContentFromEditor(), a legacy-pages-only helper — assets/js/legacy/
# app.js, bundled only in the legacy_app webpack entry) no longer applies to
# course_description_content now that it's a Vue BaseTinyEditor; swapped for
# "I fill in tinymce field ... with ..." (tinymce.get(id).setContent() +
# fire('change'), the same helper toolWiki.feature/ticket.feature use for
# BaseTinyEditor fields), confirmed live end-to-end.
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why.
@common @tools
Feature: Course description tool
  In order to manage the course description
  As a course administrator and student
  I want to edit and view the course description

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin edits the course description and sees the content
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Course description"
    And I wait for the page to be loaded
    And I click the "span.mdi-image-text" element
    And I fill in "course_description_title" with "General"
    And I fill in tinymce field "course_description_content" with "The surface web, also known as the visible web or indexed web, is the portion of the World Wide Web that is readily accessible to the general public through standard search engines such as Google and Bing, using conventional web browsers like Chrome or Firefox without requiring special software, authentication, or configuration.[1][2] It encompasses publicly available content that is crawled and indexed by search engine algorithms, allowing users to discover and navigate websites via simple URLs and keyword queries."
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "surface web"

  # @skip 2026-08-06: recurring real-CI-only failure across multiple runs
  # (`getByText('Course description', ...)` timeout). Investigated once: 2
  # of 3 local runs passed cleanly, the third failed at an unrelated point
  # (the shared login helper's own "Sign in" button) — no deterministic
  # selector/logic bug found. Deferred per explicit user instruction to stop
  # re-chasing CI-only flakes with more runs.
  # RE-ENABLED 2026-08-22. The @skip note kept below is preserved as history,
  # but its premise no longer holds: every one of those deferrals attributed the
  # failure to "concurrent-worker-load" / "real-CI-only" flakiness whose
  # suspected source was specialCase1PlatformSettings.feature mutating ~100
  # global platform settings (notably cookie_warning, a fixed bottom-of-viewport
  # overlay that intercepts pointer events) and its @long-scenario tests
  # starving the shared worker pool. SpecialCase1 has since been moved OUT of the
  # parallel batch into its own sequential CI step (@specialcase1 tag, see
  # package.json + playwright.yml), which removes that interference at the
  # source. Direct evidence it was real: toolAssessments.feature's five
  # NON-skipped scenarios were failing in CI before that change and pass after
  # it. Re-enabled to be judged on real results instead of staying dark.
  Scenario: Student views the course description
    Given I am a student
    And I wait for the page to be loaded
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Course description"
    And I wait for the page to be loaded
    Then I should see "surface web"
