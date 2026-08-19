# Ported from tests/behat/features/toolLink.feature. The legacy page
# (public/main/link/link.php) is still fully live and its field names
# (category_title, description, url, title, category_id) and button names
# (submitCategory, submitLink) are unchanged — confirmed against
# public/main/inc/lib/link.lib.php. "description" is still TinyMCE via the
# same window.setContentFromEditor helper; "category_id" is still a plain
# <select>, not Select2/AJAX.
#
# - Renamed the original's test data ("Category 1", "Chamilo", "Chamilo in
#   category 1") to mutually non-overlapping strings ("Link Category Test",
#   "Chamilo Link Test", "Chamilo Categorized Link Test"). The originals are
#   literal substrings of each other, which matters here: link.php renders
#   its list as `<div class="card">` items (not a `<table>`), each card
#   containing its own `i.mdi-delete` icon — a real risk confirmed by
#   reading the actual rendered list: with 3 cards (1 category + 2 links,
#   the categorized link nested under its category's card) all present at
#   once on a SHARED, persistent course ("TEMP", reused by many other
#   feature files), a blind `.first()` click on `i.mdi-delete` — the
#   original Behat scenario's own approach — could hit any of them,
#   including an unrelated link/category left by a different test file
#   entirely. Scoped both deletes with a new "I click the ... icon in the
#   card for ..." step (exact-text card match, not substring) instead.
# - Both original delete scenarios only asserted "should not see an error",
#   never which specific item disappeared — kept that same light-touch
#   assertion (matching original intent) but made the CLICK itself
#   deterministic, which is the part that actually mattered.
Feature: Link tool
  In order to use the link tool
  The teachers should be able to create link categories and links

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a link category
    Given I am on "/main/link/link.php?action=addcategory&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | category_title | Link Category Test |
    And I fill in editor field "description" with "Category description"
    And I press "submitCategory"
    And wait for the page to be loaded
    Then I should see "Link Category Test"
    Then I should not see an error

  Scenario: Create a link
    And I am on "/main/link/link.php?action=addlink&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo Link Test |
    And I press "submitLink"
    And wait for the page to be loaded
    Then I should see "Chamilo Link Test"
    And I should not see an error

  Scenario: Create a link with category
    Given I am on "/main/link/link.php?action=addlink&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | url   | http://www.chamilo.org |
      | title | Chamilo Categorized Link Test |
    And I select "Link Category Test" from "category_id"
    And I press "submitLink"
    And wait for the page to be loaded
    Then I should see "Chamilo Categorized Link Test"

  Scenario: Delete link
    Given I am on "/main/link/link.php?cid=3"
    And I wait for the page to be loaded
    And I click the "i.mdi-delete" icon in the card for "Chamilo Link Test"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Delete link category
    Given I am on "/main/link/link.php?cid=3"
    And I wait for the page to be loaded
    And I click the "i.mdi-delete" icon in the card for "Link Category Test"
    And wait very long for the page to be loaded
    Then I should not see an error
    Then I should not see "Link Category Test"
