# Ported from tests/behat/features/toolUsers.feature — rewritten, not
# verbatim. The Users tool is still legacy PHP (public/main/user/user.php +
# subscribe_user.php), and most of the original's own selectors are still
# accurate (confirmed live): "i.mdi-account-plus" (Add), "search_user_keyword"
# (the search field's real id, even though its `name` attribute is just
# "keyword"), "em.mdi-magnify" (the search button's icon) and "a.btn-small"
# (subscribe_user.php's "Register" button) all still exist verbatim.
#
# Two things did NOT survive and needed a real rewrite:
#
# 1. The tab id. Display::tabsOnlyLink() (public/main/inc/lib/display.lib.php)
#    generates each tab's id via `uniqid('tabs_')` — a fresh random hash on
#    EVERY page load (confirmed live: two page loads produced
#    "tabs_6a71090b8b818-1" and "tabs_6a71099b2a731-1"). The original's
#    "a#tabs_69662037e3281-1" was never a stable selector to begin with, just
#    whatever value happened to render when the scenario was authored — it
#    can never match again. Replaced with following the tab by its own
#    visible text ("Trainers"), a real getByRole('link') target.
#
# 2. The unsubscribe click needs to be scoped to the right row once more than
#    one row is showing "Unsubscribe" — same ambiguity class already
#    documented for class.feature/courseCategory.feature. This surfaces
#    differently per scenario because of how each one arrives at user.php:
#      - Scenario 1 searches directly in user.php's own list (the "keyword"
#        field is the SAME search box, same underlying FormValidator name
#        "search_user", on both user.php and subscribe_user.php — confirmed
#        live), and that search genuinely filters the subscribed-user table
#        down to the single matching row, so a plain "I follow 'Unsubscribe'"
#        is safe here (confirmed live: exactly one `a[title="Unsubscribe"]`
#        after searching).
#      - Scenario 2 registers a brand new trainer via subscribe_user.php,
#        whose "Register" action redirects back to the FULL, unfiltered
#        Trainers list (no keyword carried over) — a real live check found
#        TWO "Unsubscribe" links there (the shared box already has another
#        trainer subscribed), and a blind first-match once actually
#        unsubscribed the wrong user (admin's own trainer row, confirmed the
#        hard way and manually restored via the API before writing this
#        file). Scoped to the row containing "ywarnier" instead, exactly like
#        class.feature's own fix for the identical class of bug.
#
# 3. Entering the tool itself needs its own dedicated step, "I follow the
#    course tool 'Users'", not the shared "I follow". The course homepage's
#    "Users" tool link and the Administration SIDEBAR's own "Users" menu
#    entry both resolve to exact text "Users" on the same page — a real CI
#    failure (fresh, cold-cache install) showed the shared step's cascade
#    losing the timing race against the course-tools list's own async
#    render and falling through to its plain exact-text fallback tier,
#    which then grabbed the sidebar's entry (permanently not visible, its
#    submenu collapsed) instead of the course tool's own link, hanging for
#    the full test timeout. See the step's own comment in common.steps.ts
#    for the full DOM-level confirmation.
#
# Side-effect note: unsubscribing a user here is only "safe" on this shared
# box because of what each user's role in course TEMP actually is right now
# (confirmed live via /api/course_rel_users?course=1):
#   - amann is a PERSISTENT fixture, deliberately kept subscribed by
#     tests/behat/features/course_user_registration.feature ("leave it
#     subscribed for further tests" — its own last scenario re-registers her
#     for exactly this reason). Scenario 1 therefore re-subscribes her at the
#     end (mirroring that other feature's own convention) so this file
#     doesn't silently strip a fixture another suite still depends on.
#   - ywarnier is NOT currently subscribed to TEMP in any role. Scenario 2
#     first registers her as a trainer and unsubscribes her at the end, a
#     genuine net-zero round trip — no extra teardown needed since her
#     "subscribed" state only ever existed within this scenario itself.
@common @tools
Feature: Users tool
  In order to manage course users
  As a course administrator
  I want to search for users and unsubscribe them

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin searches for 'amann' and unsubscribes the user
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I fill in the following:
      | search_user_keyword | amann |
    And I click the "em.mdi-magnify" element
    And I wait for the page to be loaded
    Then I should see "amann"
    And I follow "Unsubscribe"
    And I confirm the popup
    And I wait for the page to be loaded
    # Teardown: amann is a persistent fixture other features rely on being
    # subscribed to TEMP (see header comment) — re-subscribe her so the
    # shared box is left exactly as this scenario found it.
    And I click the "i.mdi-account-plus" element
    And I fill in the following:
      | search_user_keyword | amann |
    And I click the "em.mdi-magnify" element
    And I click the "a.btn-small" element
    And I wait for the page to be loaded

  Scenario: Admin uses a specific tab then searches for 'ywarnier' and unsubscribes
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I follow "Trainers"
    And I wait for the page to be loaded
    And I click the "i.mdi-account-plus" element
    And I fill in the following:
      | search_user_keyword | ywarnier |
    And I click the "em.mdi-magnify" element
    And I click the "a.btn-small" element
    And I wait for the page to be loaded
    Then I should see "ywarnier"
    And I click the "a[title='Unsubscribe']" icon in the row for "ywarnier"
    And I confirm the popup
