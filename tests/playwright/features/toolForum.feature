# Rewritten against the Vue forum tool. This file used to drive the legacy
# pages under public/main/forum/ (index.php?action=add_category, newthread.php,
# reply.php, viewthread.php); those are gone — newthread.php, reply.php,
# editthread.php, editpost.php, forumqualify.php, forumsearch.php,
# viewforumcategory.php and iframe_thread.php now only deny access, and the
# course tool link resolves to /resources/forum/{courseResourceNodeId}/
# (src/CoreBundle/Tool/Forum.php). So this is a rewrite, not a port: the old
# field names (forum_category_comment aside) and the old flow no longer exist.
#
# Every selector below was confirmed against the running app with a DOM dump,
# not read off the Vue source. What that turned up, and why the steps look the
# way they do:
#
# - The list has no table and no `.card`: ForumCardList renders each category as
#   a `<section>` (title in its own `<h2>`) and each forum as an `<article>`
#   (title in its own `<a>`). Both row-scoped steps here were rescoped to that
#   in common.steps.ts.
# - Every row action is an icon-only `<button title="...">` — Delete, Edit, Hide,
#   Lock, Move up, Move down, Add forum — and the title is its only identifier,
#   so the steps pass `button[title='...']` as the selector.
# - The toolbar actions on the thread list and post list are `<a title="...">`
#   (BaseButton with a `:route`, i.e. router-links), NOT buttons. "New thread",
#   "Reply", "Back to forums" and "Search" all resolve only by that title.
# - The two Description fields differ, and getting this wrong fails as "Could
#   not find an id for field": the CATEGORY dialog's description
#   (forum_category_comment) is a plain textarea, filled with the generic fill
#   step; the FORUM dialog's (id `forum-comment`, no `name` at all) is
#   TinyMCE-backed, as are the thread message (`forum-thread-message`) and the
#   reply message (`forum-reply-message`).
# - Deletion is a PrimeVue ConfirmDialog titled "Confirmation" whose confirm
#   button reads "Yes" — NOT a native confirm() and NOT SweetAlert2, so
#   "I confirm the popup" (which only handles .swal2-container) does nothing
#   here. Every delete below presses "Yes" instead, and waits for
#   ".p-confirmdialog" first. That wait is load-bearing: without it the press
#   raced the dialog's own mount, reported success, and left the dialog sitting
#   open with the thread still there — a delete that silently never happened,
#   which then failed one step later as "Thread One is still on the page".
# - "I wait until I no longer see 'Loading'" before each assertion is not
#   padding. This tool's list mounts, then fetches /api/forum_categories,
#   /api/forums and /api/forum/action-token; measured against a dev-env box it
#   took 10-15s to settle, which outlives the 15s expect() budget an assertion
#   alone would get. That step polls for 30s.
# - Every scenario here spends most of the 90s test timeout, and the reason is
#   the environment rather than these steps: each one pays a login, a course
#   home load and then one SPA route load per view it visits, and the config's
#   own note records that regular scenarios run in ~23s in PRODUCTION mode. On a
#   dev-env box (Symfony profiler on, no warm prod cache) they land at 60-72s,
#   so a scenario that visits one view too many tips over. That is what shaped
#   the "Delete a forum thread" scenario below, and it is why nothing here fills
#   a single-entry form with the "I fill in the following:" TABLE form: that step
#   runs resolveField twice per row (a fill pass, then a verify pass), and
#   resolveField is the expensive part.
#
# Scenario order is deliberate and shared-state: the category and forum created
# by the first two scenarios are the ones every later scenario acts on, and the
# last scenario tears both down so a rerun starts from the state this file found
# (the shared TEMP course is the only fixture it touches).
Feature: Forum tool
  In order to use the Forum tool
  The teachers should be able to create forum categories, forums, forum threads

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded

  Scenario: Create a forum category
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    When I click the "button[title='Add a category']" element
    And I wait for the element "#forum-category-title" to appear
    And I fill in "forum_category_title" with "Forum Category Test"
    And I fill in "forum_category_comment" with "This is the first forum category for test"
    And I press "Create category"
    And I wait until I no longer see "Loading"
    Then I should see "Forum Category Test"
    And I should not see an error

  Scenario: Create a forum
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    When I click the "button[title='Add forum']" element
    And I wait for the element "#forum-title" to appear
    And I fill in "forum_title" with "Forum Test"
    And I fill in tinymce field "forum-comment" with "This is the first forum for test"
    And I press "Create forum"
    And I wait until I no longer see "Loading"
    Then I should see "Forum Test"
    And I should not see an error

  # The UPDATE half of the CRUD coverage, on the category rather than on the
  # forum: renaming the forum would move the anchor every later scenario uses to
  # reach it. The teardown below deletes the renamed category, not the original.
  Scenario: Edit a forum category
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    When I click the "button[title='Edit']" icon for the forum category "Forum Category Test"
    And I wait for the element "#forum-category-title" to appear
    And the field "forum_category_title" should have value "Forum Category Test"
    And I fill in "forum_category_title" with "Forum Category Renamed"
    And I press "Save"
    And I wait until I no longer see "Loading"
    Then I should see "Forum Category Renamed"
    And I should not see "Forum Category Test"
    And I should not see an error

  Scenario: Create a forum thread
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    And I follow "Forum Test"
    And I wait until I no longer see "Loading"
    When I click the "a[title='New thread']" element
    And I wait for the element "#forum-thread-title" to appear
    And I fill in "thread_title" with "Thread One"
    And I fill in tinymce field "forum-thread-message" with "This is a the first thread in a forum for test"
    And I press "Create thread"
    And I wait until I no longer see "Loading"
    Then I should see "Thread One"
    And I should not see an error

  Scenario: Reply to forum message
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    And I follow "Forum Test"
    And I wait until I no longer see "Loading"
    And I follow "Thread One"
    And I wait until I no longer see "Loading"
    When I click the "a[title='Reply to this message']" element
    And I wait for the element "#forum-reply-title" to appear
    And I fill in "reply_title" with "Reply"
    And I fill in tinymce field "forum-reply-message" with "This is a reply to the first message for test"
    And I press "Post reply"
    And I wait until I no longer see "Loading"
    Then I should see "Reply"
    And I should not see an error

  # Runs BEFORE "Delete a forum thread", exactly as it did in the legacy version
  # of this file and for a reason that survives the rewrite: deleting a thread's
  # original post re-parents its replies rather than cascading, so tearing the
  # thread down first would leave this scenario with no post to quote.
  Scenario: Quote a forum message
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    And I follow "Forum Test"
    And I wait until I no longer see "Loading"
    And I follow "Thread One"
    And I wait until I no longer see "Loading"
    When I click the "a[title='Quote this message']" element
    And I wait for the element "#forum-reply-title" to appear
    And I press "Post reply"
    And I wait until I no longer see "Loading"
    Then I should not see an error

  # Deletes "Thread One", which by now carries a reply AND a quote of that reply.
  # That is the point of running it last: those three posts form a postParent
  # chain, and deleting a thread in that shape used to answer 500 with Doctrine's
  # "A new entity was found through the relationship
  # 'Chamilo\CourseBundle\Entity\CForumPost#postParent' that was not configured
  # to cascade persist operations" — the UI showed "Could not delete thread" and
  # the thread stayed. CForumThreadRepository::delete() now clears postParent on
  # the thread's posts before removing them, so this scenario is the regression
  # test for that fix. A thread with 0 or 1 replies always deleted fine, so a
  # freshly created thread would NOT cover it.
  #
  # Deletes from the thread list, not from inside the thread: the list carries the
  # same per-thread "Delete thread" action, and opening the thread would add a
  # fourth SPA route load, which on a dev-env box tips this past the 90s timeout.
  # Unscoped because the forum this file creates holds exactly one thread.
  Scenario: Delete a forum thread
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    And I follow "Forum Test"
    And I wait until I no longer see "Loading"
    When I click the "button[title='Delete thread']" element
    And I wait for the element ".p-confirmdialog" to appear
    And I press "Yes"
    And I wait until I no longer see "Thread One"
    Then I should not see an error

  Scenario: Delete the forum and forum category
    Given I follow the course tool "Forum"
    And I wait until I no longer see "Loading"
    When I click the "button[title='Delete']" icon for the forum "Forum Test"
    And I wait for the element ".p-confirmdialog" to appear
    And I press "Yes"
    And I wait until I no longer see "Forum Test"
    And I click the "button[title='Delete']" icon for the forum category "Forum Category Renamed"
    And I wait for the element ".p-confirmdialog" to appear
    And I press "Yes"
    And I wait until I no longer see "Forum Category Renamed"
    Then I should not see an error
