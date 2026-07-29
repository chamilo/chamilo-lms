# Ported from tests/behat/features/sessionAccess.feature — required substantial
# rework, not a verbatim port, because session_add.php's multi-step flow has
# been redesigned since the original suite was written:
#
# - Real field name is `title`, not `name` (SessionManager::setForm(),
#   FormValidator::addText('title', ...)) — same name->title rename pattern
#   already confirmed systemic in courseCategory (gotcha 17). The original's
#   "| name | Session1 |" table rows never matched anything real.
# - coach_username/courses/users are ALL FormValidator::addSelectAjax() now
#   (Select2 + a real AJAX search endpoint) — not a plain Select2 with no
#   bound options (coach_username) or a separate raw-injection mechanism
#   (courses/users) like the original assumed. All three now go through the
#   existing "I select ... from the ajax select ..." step (already proven
#   against course_add.php's category field) — no numeric ids needed at all.
# - coach_username's AJAX search (session.ajax.php?a=search_general_coach)
#   hard-filters to ROLE_TEACHER server-side now — admin (ROLE_ADMIN/
#   ROLE_GLOBAL_ADMIN/ROLE_USER, no ROLE_TEACHER) can never be a valid coach
#   through this real search, unlike the original's synthetic-injection
#   approach which bypassed that check entirely. Uses "jmontoya" (a real
#   teacher not otherwise referenced in this feature) as coach for both
#   sessions instead — deliberately NOT mmosquera/ywarnier, since both of
#   those are later used as the actual access-check subjects (the "connect
#   to Session X" scenarios below) and making either of them ALSO the coach
#   could change what access they get for reasons unrelated to what those
#   scenarios are actually testing (session subscription, not coach status).
# - add_courses_to_session.php's heading used to read as one combined string
#   ("Add courses to this session (Session1)"); it's been redesigned with the
#   session title as a separate line — asserted as two separate "I should
#   see" checks instead of one combined string.
# - Found and fixed a real, pre-existing bug this exposed:
#   SessionManager::getCountUsersInCourseSession() (sessionmanager.lib.php)
#   used raw DQL with the old Bundle:Entity alias syntax
#   ("ChamiloCoreBundle:SessionRelCourseRelUser" etc.), which Doctrine can no
#   longer resolve ("Class ... is not defined") — a 500 on
#   resume_session.php, the page reached right after subscribing users to a
#   session. Fixed to use ::class references, matching every other query in
#   the codebase. Two more raw Bundle:Entity call sites exist (template.lib.php,
#   usermanager.lib.php) — not on this code path, not fixed here, worth a
#   follow-up.
# - BaseButton's icon renders as <span class="mdi mdi-delete">, not <i> (only
#   legacy jqGrid pages render icons as <i>) — used a tag-agnostic
#   ".mdi-delete" selector instead of "i.mdi-delete" in this file only (the
#   shared step itself is unchanged, other features' own "i.mdi-delete" still
#   correctly targets their legacy jqGrid icons).
# - Fixed 4 occurrences of the same "wait THE page to be loaded when ready"
#   typo (missing "for") already found and fixed once in course.feature
#   (gotcha in memory) — never matched any real Behat step in the original
#   suite either, since FeatureContext.php only registers "wait FOR the
#   page...".
# - "TEMPPRIVATE" is the course CODE (not title) of course.feature's "Create
#   a private course before testing" ("title" = "TEMP_PRIVATE" with an
#   underscore; course_add.php's code generation strips it). That scenario
#   is NOT part of the dedicated "Seed test course" CI step (which only
#   covers plain "TEMP") — same cross-file race gotcha already fixed once
#   for TEMP/cid=1 (course_user_registration.feature). Needs the same fix:
#   a dedicated "Seed private course" CI step before the main parallel batch
#   (see playwright.yml / package.json — added alongside this feature).
# - session_list.php (used by the original's two "Delete session" scenarios)
#   is now a dead 2-line stub — session list/search/delete has moved to
#   /admin/session-list (SessionList.vue, BaseTable, `keyword` query param,
#   row-scoped delete button, PrimeVue ConfirmDialog "Yes" — same dead-page
#   situation as class.feature's usergroups.php). Rewrote both delete
#   scenarios against the current Vue page instead of porting verbatim.
Feature: Session access
  In order to access a session
  The teacher must be registered as a session coach for this course

  Scenario: Create session 1
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    When I fill in the following:
      | title | Session1 |
    And I select "jmontoya" from the ajax select "coach_username"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session"
    And I should see "Session1"
    Then I select "TEMPPRIVATE" from the ajax select "courses"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error
    And I should see "Subscribe users to this session"
    Then I select "fapple" from the ajax select "users"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Session1"
    Then I should see "TEMPPRIVATE"
    Then I should see "fapple"

  Scenario: Check if same session exists.
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title | Session1 |
    And I select "jmontoya" from the ajax select "coach_username"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Session title already exists"

  Scenario: Create session 2
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Session2 |
    And I select "jmontoya" from the ajax select "coach_username"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session"
    And I should see "Session2"
    Then I select "TEMPPRIVATE" from the ajax select "courses"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error
    Then I should see "Subscribe users to this session"
    Then I select "Michela" from the ajax select "users"
    And I press "submit"
    And wait for the page to be loaded
    Then I should see "Session2"
    Then I should see "TEMPPRIVATE"
    Then I should see "mmosquera"

  Scenario: ywarnier connects to Session1
    Given I am not logged
    Given I am logged as "ywarnier"
    Then I am on course "TEMPPRIVATE" homepage in session "Session1"
    And wait for the page to be loaded when ready
    Then I should not see "You are not allowed"

  Scenario: ywarnier connect to Session 2
    Given I am not logged
    Given I am logged as "ywarnier"
    Then I am on course "TEMPPRIVATE" homepage in session "Session2"
    And I wait for the page to be loaded
    Then I should see "not allowed"

  Scenario: ywarnier connect to course TEMPPRIVATE inside a session that doesn't exists
    Given I am not logged
    Given I am logged as "ywarnier"
    And I am on "/course/2/home?sid=2000&gid=0"
    And wait for the page to be loaded when ready
    Then I should see "Session not found"

  Scenario: mmosquera connect to Session 1
    Given I am not logged
    Given I am logged as "mmosquera"
    Then I am on course "TEMPPRIVATE" homepage in session "Session1"
    And wait for the page to be loaded when ready
    Then I should see "not allowed"

  Scenario: mmosquera connect to Session 2
    Given I am not logged
    Given I am logged as "mmosquera"
    And wait for the page to be loaded
    Then I am on course "TEMPPRIVATE" homepage in session "Session2"
    And wait for the page to be loaded when ready
    Then I should not see "You are not allowed"

  Scenario: Delete session "Session2"
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Session2"
    And wait for the page to be loaded
    And I click the ".mdi-delete" icon in the row for "Session2"
    And I press "Yes"
    And wait for the page to be loaded
    Then I should not see "Session2"
    And I should not see an error

  Scenario: Delete session "Session1"
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Session1"
    And wait for the page to be loaded
    And I click the ".mdi-delete" icon in the row for "Session1"
    And I press "Yes"
    And wait for the page to be loaded
    Then I should not see "Session1"
    And I should not see an error
