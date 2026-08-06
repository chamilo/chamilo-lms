# Ported from tests/behat/features/actionUserCheck.feature — rewritten, not
# verbatim. public/main/admin/user_list.php is now a deprecated 2-line stub
# left over after 2.0 RC2 (confirmed live: a blank 200 page) — the real user
# list has been migrated to Vue (UserList.vue, "/admin/user-list"), reachable
# from the "User list" link on /admin (see adminUserBlock.feature's own
# already-ported "Open User list" scenario).
#
# Confirmed live: navigating straight to "/admin/user-list?keyword=admin"
# pre-fills the search box from the query string (UserList.vue reads
# window.location.search on mount) and runs the same keyword search a manual
# submit would, so no extra "fill in / press Search" steps are needed. The
# admin fixture user (John Doe, username "admin") still exists and matches.
# Following "John" (the first-name cell's own link, not a row click) lands on
# "/main/admin/user_information.php?user_id=1" — still a real, working legacy
# page (not a stub) — which shows "John Doe" as its own heading, same as the
# original scenario's final check. The "anon" scenario is unchanged in
# substance: the anonymous fixture user (Anonymous Joe, username "anon")
# still exists and is the sole match for that keyword. Drops the original's
# second, duplicate "Then I should see 'anon'" line — there is only ever one
# matching row, so a second identical assertion checks nothing new; it reads
# as a copy/paste artifact rather than an intentional check.
Feature: User check after installation

  Scenario: Check admin information
    Given I am a platform administrator
    And I am on "/admin/user-list?keyword=admin"
    And I wait for the page content to settle
    Then I should see "admin"
    Then I follow "John"
    And I wait for the page to be loaded
    Then I should see "John Doe"

  Scenario: Check anon information
    Given I am a platform administrator
    And I am on "/admin/user-list?keyword=anon"
    And I wait for the page content to settle
    Then I should see "anon"
