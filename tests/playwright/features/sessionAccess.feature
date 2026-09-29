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
#   for TEMP/cid=3 (course_user_registration.feature). Needs the same fix:
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

  # REAL CI RUN FOUND (3rd round): "Then I should see 'not allowed'" is
  # flaky-to-always-failing for the two access-denied scenarios below
  # (ywarnier/Session2, mmosquera/Session1). Traced the exact server flow
  # via a real CI trace.zip's network log and Location headers:
  #   GET /course/2/home?sid=<X>            -> 302 Location: /
  #   GET /                                 -> 200 (full reload)
  # CidReqListener denies access (CourseVoter/SessionVoter::VIEW false) by
  # throwing AccessDeniedHttpException — for a plain browser navigation
  # (not XHR/JSON), ExceptionListener.php catches it, adds the message to
  # the Symfony session flash bag, and redirects to the `index` route (/).
  # The Vue shell then boots, reads #app[data-flashes] and fires a toast,
  # but that toast is NOT guaranteed to still be in the DOM by the time the
  # assertion polls (confirmed failing in 2 of 3 identically-shaped
  # scenarios in the same CI run) — same "transient toast, not a durable
  # signal" trap already hit and fixed elsewhere in this suite. CORRECTION
  # to an earlier version of this comment: a first pass misread the
  # trace's network log (out of chronological order in the raw JSON) and
  # concluded the final URL was "/courses" — re-sorted by timestamp AND
  # confirmed locally, the "_route_name=MyCourses" calls actually happen
  # right after LOGIN (login.js's own post-auth landing target), BEFORE
  # this scenario's own navigation; the real final resting URL after the
  # denial redirect is the site root "/" (Index route), which nothing
  # further redirects away from. Asserting that instead, via a dedicated
  # step (a plain substring "the URL should contain '/'" would match any
  # URL, since every path starts with "/").
  Scenario: ywarnier connect to Session 2
    Given I am not logged
    Given I am logged as "ywarnier"
    Then I am on course "TEMPPRIVATE" homepage in session "Session2"
    And I wait for the page to be loaded
    Then the URL should be the site root

  # FINAL (5th round) — this scenario's assertion flip-flopped repeatedly
  # before settling; recording the full history so it doesn't happen again.
  # Round 3: "not allowed" (a transient toast) failed on real CI, "fixed" to
  # "the URL should be the site root" for this whole family of scenarios.
  # Round 4: reverted THIS scenario specifically back to a text assertion
  # ("Session not found"), reasoning that an unresolvable session id hits
  # CidReqListener's NotFoundHttpException branch, not AccessDeniedHttpException
  # — verified true LOCALLY against playwright.chamilo.net at the time, but
  # that local instance had already accumulated stale course access for
  # ywarnier on TEMPPRIVATE from many earlier repeated test runs, which a
  # genuinely fresh install never has. A real fresh-CI run disproved it
  # cleanly: ywarnier has NO course-level access to TEMPPRIVATE via this
  # direct numeric cid/sid URL (unlike the "connects to SessionX" scenarios,
  # which go through main/course_home/redirect.php's own CODE+name
  # resolution/subscription logic, not this raw path) — CourseVoter::VIEW
  # denies FIRST, before the invalid session id is ever even looked up, so
  # this hits the exact same AccessDeniedHttpException -> flash -> redirect
  # to "/" -> unreliable toast mechanism as its two siblings above (confirmed
  # via the CI trace's Location header: 302 to "/"), not the durable inline
  # alert. Same fix as those two: assert the durable URL, not toast text.
  #
  # @skip 2026-08-06: recurring real-CI-only failure across two separate runs,
  # identical both times — lands GRANTED on
  # "http://localhost/course/2/home?sid=2000&gid=0" instead of the expected
  # denial redirect to the site root. Investigated twice this session, never
  # reproduced locally:
  #   Round 1: reproduced this single scenario in isolation -> passed cleanly.
  #   Read CourseVoter/CourseAccessResolver end to end -> access logic is
  #   correct. Queried the live DB directly -> ywarnier has NO course_rel_user
  #   row for TEMPPRIVATE (cid=4 since the installer's two demo courses took
  #   cid=1/cid=2; was cid=2 when this note was first written) that would
  #   grant access.
  #   Round 2 (this attempt): ran the FULL feature file (all 10 scenarios,
  #   `sessionAccess.feature.spec.js`) instead of just this scenario, in case
  #   an earlier scenario's leftover state (session creation, prior access
  #   checks) only manifests when preceded by the rest of the file -> all 10
  #   scenarios passed, including this one. Also checked whether "sid=2000"
  #   is a real collision (i.e. "a session that doesn't exist" no longer
  #   holds because some concurrent test created session id 2000 for real):
  #   `SELECT id, title FROM session` shows only ids 4-7 exist, and
  #   `SHOW TABLE STATUS LIKE 'session'` shows AUTO_INCREMENT=13 — id 2000 is
  #   nowhere close to being real on this box, and `course` id 2 does
  #   correctly resolve to TEMPPRIVATE, so the scenario's own premise is
  #   sound; this is not an id-collision bug. No further concrete lead found
  #   (a suspected admin/ywarnier session-fixation race across the preceding
  #   "I am not logged" -> "I am logged as 'ywarnier'" pair was considered but
  #   not confirmed — Symfony's built-in /logout invalidates the session by
  #   default, and nothing in this file overrides that). Per explicit user
  #   preference for deferring over a third chase, `@skip`-ing just this
  #   scenario (not the whole file — its sibling scenarios above/below must
  #   keep running). Revisit if a future CI trace narrows the cause further.
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
  Scenario: ywarnier connect to course TEMPPRIVATE inside a session that doesn't exists
    Given I am not logged
    Given I am logged as "ywarnier"
    # ROOT-CAUSED 2026-08-23. This used to navigate to the hardcoded path
    # "/course/2/home?sid=2000&gid=0" and it was simply the WRONG COURSE.
    #
    # Course 2 has not been TEMPPRIVATE since the installer started shipping
    # demo courses: DemoCoursesFixtures now creates "AI Act" (1) and "Using
    # Chamilo" (2) before any suite seeding, so TEMPPRIVATE moved to 4. Exactly
    # the same shift that forced the suite-wide cid=1 -> cid=3 migration, but in
    # PATH form ("/course/N/home"), which that pass did not cover.
    #
    # Why it failed rather than just testing the wrong thing: visibility. The
    # demo course "Using Chamilo" is visibility=2 (OPEN_PLATFORM), so ANY
    # logged-in user may enter it and access is legitimately GRANTED — the
    # scenario then failed asserting a denial that correctly never happened.
    # TEMPPRIVATE is visibility=1 (PRIVATE), where the denial is real. Measured
    # all three as ywarnier:
    #   /course/2/home?sid=2000                     -> stays on the course (granted)
    #   /course/4/home?sid=2000                     -> 302 to "/"  (denied)
    #   redirect.php?cidReq=TEMPPRIVATE&sid=2000    -> 302 to "/"  (denied)
    #
    # This also explains the "never reproduced locally" note above, which is now
    # obsolete: on the older long-lived dev box course 2 genuinely WAS
    # TEMPPRIVATE, so the denial happened and the scenario passed. Only a fresh
    # install — i.e. every CI run — shifts the ids. The comment above even
    # records the symptom verbatim ("lands GRANTED on /course/2/home"); the
    # missing step was noticing that course 2 was no longer the private course.
    #
    # Addressed by course CODE rather than by id 4, deliberately: hardcoding an
    # id is what broke this in the first place, and course_home/redirect.php
    # resolves the code while still honouring the bogus sid, so it exercises the
    # identical CidReqListener branch (a session id that does not resolve) with
    # nothing left to drift.
    And I am on "/main/course_home/redirect.php?cidReq=TEMPPRIVATE&sid=2000"
    And wait for the page to be loaded when ready
    Then the URL should be the site root

  Scenario: mmosquera connect to Session 1
    Given I am not logged
    Given I am logged as "mmosquera"
    Then I am on course "TEMPPRIVATE" homepage in session "Session1"
    And wait for the page to be loaded when ready
    Then the URL should be the site root

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
