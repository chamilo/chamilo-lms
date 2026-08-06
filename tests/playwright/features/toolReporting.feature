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

  Scenario: Admin navigates reporting pages and checks them
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | search | pperez |
    And I press "Search"
    And I wait for the page to be loaded
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
    And I click the "[title='Unsubscribe']" element
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "Perez"
