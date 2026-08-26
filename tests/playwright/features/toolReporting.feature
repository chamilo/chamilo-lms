# Ported from tests/behat/features/toolReporting.feature — rewritten, not
# verbatim. Drops "I zoom out to maximum" (see adminHealthBlock.feature's
# header comment for why) and the ambiguous "I follow 'Reporting'" (course
# TEMP's homepage has TWO elements with that exact visible text: the global
# Administration-sidebar "Reporting" button — /main/my_space/index.php — and
# the course tool's own "Reporting" link — confirmed live via a DOM dump).
# Uses "I follow the course tool 'Reporting'" (already established by
# toolUsers.feature's own header comment for the identical ambiguity class)
# to target the course-tools list specifically.
#
# The original scenario's "Then I should see 'Andrea Costea'" assumed acostea
# was already subscribed to TEMP by suite ordering elsewhere in the Behat
# suite — confirmed live this is NOT the case here (course_rel_user only has
# "admin" subscribed to TEMP on a fresh install/reseed). Per this project's
# self-containment convention for ported files, this scenario subscribes a
# learner itself (via the Users tool's Subscribe view, same flow documented in
# toolUsers.feature) before checking the report, and unsubscribes them again at
# the end so course TEMP is left exactly as found — mirroring toolUsers.
# feature's own teardown convention for the exact same "TEMP is shared by
# every other file" constraint (see this suite's course.feature header
# comment).
#
# REAL CI RACE FOUND: this scenario originally used the shared "acostea"
# fixture for its own subscribe-check-unsubscribe round trip, same as
# toolAssessments.feature. Both files touch the identical (acostea, TEMP)
# pairing, and `fullyParallel: false` only serializes scenarios WITHIN one
# file — different files still run concurrently across workers (see this
# suite's playwright.config.ts own comment on that exact distinction). Live
# CI showed acostea's row vanishing from the Reporting learner list moments
# after this scenario's own "Register" click confirmed her subscribed, and
# separately toolAssessments.feature's own subscribe-picker check failing to
# find her at all (subscribe_user.php's picker excludes users already
# subscribed via `cu.user_id IS NULL` — confirmed by reading
# public/main/user/subscribe_user.php's get_user_data()) — both symptoms are
# exactly what happens when the OTHER file's own unsubscribe/subscribe lands
# mid-window. Fixed by switching this file's own round trip to "pperez"
# (Pedro Perez, status 5/STUDENT in tests/datafiller/data_users.php) — a
# fixture confirmed unused by any other .feature file or step in this suite,
# so this file's own subscribe/verify/unsubscribe cycle can never be raced by
# another file's independent management of the SAME user+course pairing.
# toolAssessments.feature was given its own, different, equally-unused
# dedicated learner ("norizales") for the same reason — using the same
# replacement user in both files would just relocate the identical race.
#
# Every reporting sub-page below (course_log_groups.php, course_log_resources.
# php, course_log_tools.php, tracking/exams.php, tracking/course_log_events.
# php, tracking/lp_report.php) is still legacy PHP, confirmed live unchanged
# from the original scenario — only the entry point and the learner-visibility
# precondition needed fixing.
@common @tools
Feature: Reporting tool
  In order to check reporting pages
  As a course administrator
  I want to open each reporting link and ensure the pages load correctly

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  # @skip 2026-08-06: this scenario already went through one fix-and-verify
  # round this session (the acostea/TEMP cross-file subscription race
  # documented above, fixed by switching to the dedicated "pperez" fixture
  # and confirmed passing locally). Real CI has since failed it again with a
  # different, generic "Target page, context or browser has been closed"
  # signature. Investigated further this round: reproduced 2 real failures
  # locally (not just in CI) on repeated runs of this exact scenario, but at
  # TWO DIFFERENT steps each time — once at the teardown's "Then I should
  # not see 'Perez'" (the Unsubscribe-then-confirm flow's success flash
  # message rendered while the learner row was still present), once at the
  # very first "Then I should see 'Perez'" search-picker check (this
  # scenario's own earlier "Register" step never actually got that far).
  # Both are consistent with this suite's own already-documented class of
  # "I wait for the page to be loaded" (a plain domcontentloaded) not
  # reliably covering an AJAX-driven legacy action's own async completion
  # (see "I wait for the page content to settle"'s header comment for the
  # same underlying gap, there fixed for a different file) rather than a
  # single, confident, specific defect in this scenario's own steps — but
  # unlike the purely-CI-only failures elsewhere in this suite, this one
  # reproduced twice locally at two different points, so it needs more
  # investigation than is safe to commit to in this pass without risking an
  # unverified guess-fix. Deferring per this session's explicit
  # don't-over-chase guidance rather than landing something unconfirmed.
  # Revisit together with the other @skip'd scenarios in this suite.
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
  Scenario: Admin navigates reporting pages and checks them
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page content to settle
    # Wait for a Subscribe-view-only control before touching the search box:
    # both the course-users LIST view and SUBSCRIBE view contain
    # [name="search"], and "I wait for the page to be loaded"
    # (domcontentloaded) is a no-op for this client-side route change, so the
    # fill could land on the wrong input while the view was still swapping.
    # Same fix and reasoning as toolUsers.feature's own subscribe step.
    And I wait for the element "[title='Register']" to appear
    And I fill in the following:
      | search | pperez |
    And I submit the field "search"
    And I wait for the page content to settle
    Then I should see "Perez"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Reporting"
    And I wait for the page to be loaded
    Then I should see "Pedro Perez"

    # Group reporting
    And I follow "Group reporting"
    And I wait for the page to be loaded
    Then I should not see an error

    # Report on resources
    And I follow "Report on resources"
    And I wait for the page to be loaded
    Then I should not see an error

    # Course report
    And I follow "Course report"
    And I wait for the page to be loaded
    Then I should not see an error

    # Exam tracking
    And I follow "Exam tracking"
    And I wait for the page to be loaded
    Then I should not see an error

    # Audit report
    And I follow "Audit report"
    And I wait for the page to be loaded
    Then I should not see an error

    # Learning paths generic stats
    And I follow "Learning paths generic stats"
    And I wait for the page to be loaded
    Then I should not see an error

    # Teardown: leave course TEMP exactly as found (only "admin" subscribed).
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    Then I should see "Perez"
    # ROW-SCOPED unsubscribe. This was an UNSCOPED
    # `I click the "[title='Unsubscribe']" element`, which clicks whichever
    # Unsubscribe button comes first in DOM order — and course TEMP has several
    # subscribed learners (acostea, fapple, amann, pperez), so it unsubscribed
    # SOMEBODY ELSE. Two consequences, both observed:
    #   1. This scenario then failed its own final assertion, because Perez was
    #      of course still subscribed ("expect(body).not.toContainText('Perez')").
    #   2. Far worse, it silently stripped another file's fixture. This is the
    #      best explanation for a failure that took a long time to attribute:
    #      a full run ended with fapple missing from course_rel_user even
    #      though the seed had subscribed him, which broke five toolGroup
    #      "Add fapple to Group 000X" scenarios and cascaded into six more.
    #      Under parallel execution this file can run alongside toolGroup, so
    #      the victim was effectively random.
    # Unlike toolUsers.feature's equivalent scenario, this one never searches
    # first, so it cannot rely on the list being narrowed to a single row —
    # scoping to the row is the only correct fix. Uses the existing row-scoped
    # step, the same convention class.feature/courseCategory.feature already
    # use for exactly this hazard.
    And I click the "[title='Unsubscribe']" icon in the row for "Perez"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Perez"
