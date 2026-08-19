# Ported from tests/behat/features/toolForum.feature, verbatim — confirmed
# against the current codebase that every field name/id (forum_category_title,
# forum_category_comment, forum_title, forum_comment, post_title, post_text),
# button name (SubmitForumCategory, SubmitForum, SubmitPost), icon class
# (i.mdi-format-quote-open, i.mdi-comment-arrow-right-outline, i.mdi-delete,
# i.mdi-comment-quote), the native confirm() delete dialog, and the
# "Quoting" string are all unchanged (public/main/forum/forumfunction.inc.php,
# viewforum.php, viewthread.php).
Feature: Forum tool
  In order to use the Forum tool
  The teachers should be able to create forum categories, forums, forum threads

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a forum category
    Given I am on "/main/forum/index.php?action=add_category&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | forum_category_title   | Forum Category Test |
    And I fill in editor field "forum_category_comment" with "This is the first forum category for test"
    And I press "SubmitForumCategory"
    And wait for the page to be loaded
    And I should see "Forum Category Test"
    Then I should not see an error

  Scenario: Create a forum
    Given I am on "/main/forum/index.php?action=add_forum&cid=3"
    And I wait for the page to be loaded
    When I fill in the following:
      | forum_title   | Forum Test |
    And I fill in editor field "forum_comment" with "This is the first forum for test"
    And I press "SubmitForum"
    And wait very long for the page to be loaded
    Then I should see "Forum Test"
    And I should not see an error

  Scenario: Create a forum thread
    Given I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    And I click the "i.mdi-format-quote-open" element
    And wait for the page to be loaded
    When I fill in the following:
      | post_title | Thread One |
    And I fill in editor field "post_text" with "This is a the first thread in a forum for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Thread One"
    And I should not see an error

  Scenario: Reply to forum message
    Given I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    When I follow "Thread One"
    And I wait for the page to be loaded
    When I click the "i.mdi-comment-arrow-right-outline" element
    And I wait for the page to be loaded
    And I fill in the following:
      | post_title | Reply |
    And I fill in editor field "post_text" with "This is a reply to the first message for test"
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Reply"
    Then I should not see an error

  # Runs BEFORE "Delete a forum thread" below, unlike the original Behat
  # order — real, confirmed CI failure: deletePost() (forumfunction.inc.php)
  # doesn't cascade-delete a thread's replies when its original post is
  # deleted, it re-parents them and leaves them in place (a thread is only
  # actually removed once its last surviving post is gone). viewthread.php
  # recomputes "which post is the thread's OP" on every load as MIN(iid)
  # among surviving posts, and OP posts don't render a quote icon at all
  # (viewthread.php's `$isOp` gate). So running "Delete a forum thread"
  # (which deletes "Thread One", the OP) first left "Reply" promoted to OP
  # by the time this scenario ran, permanently hiding the quote icon it
  # depends on — deterministic given this file's fixed scenario order, not a
  # flake. Reordering (quote, then delete) sidesteps this without touching
  # the app's own deliberate re-parenting behavior.
  Scenario: Quote a forum message
    Given I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    When I follow "Thread One"
    And I wait for the page to be loaded
    When I click the "i.mdi-comment-quote" element
    And I wait for the page to be loaded
    And I press "SubmitPost"
    And wait for the page to be loaded
    Then I should see "Quoting"

  Scenario: Delete a forum thread
    Given I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    And I follow "Forum Test"
    And I wait for the page to be loaded
    Then I follow "Thread One"
    And I wait for the page to be loaded
    Then I click the "i.mdi-delete" element
    And I confirm the popup
    And wait for the page to be loaded
    Then I should not see an error

  # Not in the original Behat source (which never cleaned up "Forum Test"/
  # "Forum Category Test" either) — added after a real, reproduced-live
  # issue: this file's own "Forum Test" forum/"Forum Category Test" category
  # have no unique-name constraint, so a repeated run of this file against a
  # database that already has one (a rerun on a reused/self-hosted box, or
  # two runs overlapping) leaves TWO of each on "/main/forum/index.php".
  # "I follow {string}" (common.steps.ts) resolves ambiguous exact-text
  # matches with a plain `.first().click()` — no error, just a silent click
  # on whichever one DOM order happens to put first. Directly reproduced
  # locally by running this file twice without reseeding the database: the
  # second pass's own "Create a forum thread" scenario silently added
  # "Thread One" to the FIRST run's stale, already fully-torn-down "Forum
  # Test" instead of the one it had just created — every scenario still
  # reported green, but which forum/thread each step actually acted on
  # became non-deterministic, which is exactly the kind of drift that can
  # surface as the cascading failures this file previously hit in CI.
  # Tearing down the forum and its category here — the only "Forum Test"/
  # "Forum Category Test" this file itself created — keeps every future run
  # starting from the same database state this one found it in, regardless
  # of environment reuse.
  # Uses the dedicated forum-scoped/category-scoped steps (common.steps.ts)
  # rather than a plain "I click the ... element": a category's own
  # delete-category icon and a forum's own delete-forum icon render via the
  # identical `i.mdi-delete` class as every OTHER category/forum's own
  # delete icon on the same page — an unscoped click is only safe as long as
  # exactly one category and one forum ever exist, which is precisely the
  # invariant this teardown exists to guarantee. Scoping to the exact title
  # removes that assumption instead of relying on it.
  Scenario: Delete the forum and forum category
    Given I am on "/main/forum/index.php?cid=3"
    And I wait for the page to be loaded
    Then I click the "i.mdi-delete" icon for the forum "Forum Test"
    And I confirm the popup
    And wait for the page to be loaded
    Then I click the "i.mdi-delete" icon for the forum category "Forum Category Test"
    And I confirm the popup
    And wait for the page to be loaded
    Then I should not see an error
