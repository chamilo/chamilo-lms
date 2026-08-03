# Ported from tests/behat/features/toolPortfolio.feature — rewritten, not
# verbatim. The Portfolio tool has been migrated to Vue
# (PortfolioListView.vue/PortfolioFormView.vue/PortfolioItemView.vue,
# /resources/portfolio/...) since the original scenario was written.
# Confirmed live via a throwaway script:
# - "portfolio_title" is a plain BaseInputText <input> here (titleAsHtml is
#   off by default — BaseTinyEditor for the title only renders when that
#   platform setting is on), NOT a TinyMCE field like the original assumed —
#   so it's filled with the plain "I fill in ... with ..." step, not
#   "I fill in editor field ... with ...".
# - "portfolio_content" IS a real TinyMCE-backed <textarea>
#   (BaseTinyEditor), so it keeps "I fill in tinymce field ... with ...".
# - The create form's Save button is a real BaseButton with name="save"
#   (confirmed live), matching the original's lowercase "I press 'save'"
#   literally.
# - Saving lands directly on the new item's page (no intermediate list
#   view), where the title is immediately visible — same end state the
#   original's "Then I should see ..." expected either way.
# - The comment dialog's editor is "portfolio_comment_content", still a real
#   TinyMCE field (BaseTinyEditor, unconditional there), and its Save button
#   is unnamed/iconed (no id/name/title match) — "I press 'Save'" already
#   falls through to pressButton()'s exact-visible-text tier for this case
#   (same as toolWiki.feature/toolDropbox.feature's Save buttons).
# Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
# comment for why.
@common @tools
Feature: Portfolio tool
  In order to document learning evidence
  As a course participant
  I want to create a portfolio item and comment on it in the Vue interface

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Create a portfolio item in the Vue interface
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Portfolio"
    And I wait for the page content to settle
    And I follow "Add"
    And I wait for the page to be loaded
    And I fill in "portfolio_title" with "Modern portfolio evidence"
    And I fill in tinymce field "portfolio_content" with "Evidence created from the Vue Portfolio form"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Modern portfolio evidence"

  Scenario: Add a comment in the Vue interface
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Portfolio"
    And I wait for the page content to settle
    And I follow "Modern portfolio evidence"
    And I wait for the page to be loaded
    And I press "Add a new comment"
    And I fill in tinymce field "portfolio_comment_content" with "Comment created from the Vue Portfolio dialog"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Comment created from the Vue Portfolio dialog"
