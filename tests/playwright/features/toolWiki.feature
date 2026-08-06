# Ported from tests/behat/features/toolWiki.feature — rewritten, not
# verbatim. The Wiki tool has been migrated to Vue (WikiPageView.vue/
# WikiPageFormView.vue, /resources/wiki/...) since the original scenario was
# written: "i.mdi-pencil" no longer matches anything (confirmed live — the
# edit toolbar icon is now a `<span class="mdi mdi-pencil">` inside an `<a
# title="Edit page">`, not a bare `<i>`), the editor field id is now
# "wiki_page_content" (not "content"), and there is no "wiki_SaveWikiChange"
# button anymore (a plain "Save" BaseButton). Drops "I zoom out to maximum"
# — see adminHealthBlock.feature's header comment for why. Verified live
# end-to-end: following "Edit page" (matched by its real title attribute,
# same as any other "I follow" target), filling the tinymce field, and
# pressing "Save" does land back on the wiki page with "New Wiki" visible.
#
# Uses "I wait for the page content to settle" (not the plain "wait for the
# page to be loaded", domcontentloaded-only) between following "Wiki" and
# "Edit page" — real CI-reproducible failure: "I follow"'s title-attribute
# tier does a plain, non-retrying `.count()` check, so it can miss the
# toolbar's `a[title="Edit page"]` if the Wiki page's own async content
# (wikiPage.canEdit etc.) hasn't finished rendering yet right after the SPA
# route transition, falling through all the way to the final unbounded
# exact-text fallback and hanging for the rest of the test timeout.
@common @tools
Feature: Wiki tool
  In order to edit a wiki in a course
  As a course administrator
  I want to edit the wiki content and see it listed

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin edits a wiki and sees the new content
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Wiki"
    And I wait for the page content to settle
    And I follow "Edit page"
    And I wait for the page to be loaded
    And I fill in tinymce field "wiki_page_content" with "New Wiki"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "New Wiki"
