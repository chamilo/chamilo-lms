# Ported from tests/behat/features/toolExerciseTeacher.feature — rewritten,
# not verbatim. This source file is BYTE-IDENTICAL to
# tests/behat/features/toolExerciseAdmin.feature (diff confirmed, same
# Gherkin content top to bottom, only the Background's "I am logged as ..."
# differs in spirit even though the literal text is the same "ywarnier"
# login) — so every Vue-migration finding documented in
# tests/playwright/features/toolExerciseAdmin.feature's own header comment
# (legacy selectors gone: "i.mdi-pencil"/"i.mdi-cog", "weighting[N]"/
# "correct[N]"/"answerN"/"commentN", bootstrap-select "matches_2", the
# CKEditor4-only "ckeditor in the page" step; the new common.steps.ts step
# definitions this tool needs: "I follow the question type ...", "I fill in
# the answer/comment ...", "I mark answer ... as correct", "I fill in
# matching pair ...", "I select ... from matching select ...", "I fill in
# blank ... with ...", "I fill in the open answer with ...", "I check every
# ... option on the page", "I press 'Next question' until ... appears") also
# applies verbatim here and is reused rather than reinvented.
#
# Confirmed live, as its own genuine role check (not just assumed identical
# to the admin port): every kept scenario below behaves the same for a
# TEACHER of the course as it did for a platform admin — question CRUD,
# duplicate, both Excel imports, taking the exercise as a student, the
# teacher-side report, and both deletions all work identically under
# mmosquera's course-teacher permissions. No permission gap was found.
#
# Same THREE scenarios DROPPED as toolExerciseAdmin.feature, for the exact
# same reason (this source file is identical, so the defects are identical):
# - "Check exercise result": re-answers a mismatched question set
#   ("semantics"/"RNASL"/choice_id_6_*) under a "Results and feedback" link
#   that never corresponds to this file's own questions, in the original
#   Behat suite either. "Teacher checks exercise results" below covers the
#   genuine modern equivalent (the read-only "Learner score" attempts table).
# - "Teacher looks at exercise results by categories" and "Delete session":
#   both depend on a session literally named "Session Exercise" that is
#   never created anywhere in this file (or its Behat original) — planted,
#   if at all, by some unrelated file. Re-verified by reading this exact
#   source file, not just trusting the admin port's summary: confirmed no
#   scenario here creates that session either.
#
# Course-lifecycle strategy (a deliberate departure from the admin port):
# toolExerciseAdmin.feature reuses the shared course "TEMP", safe there only
# because every OTHER file's own scenarios treat TEMP's Tests tool as
# theirs to fill/empty exclusively at admin scope. Reusing TEMP a second
# time here would race it: different feature files can run in different
# parallel workers, and both files create identically-named exercises/
# categories ("Exercise 1", "Category 1", "Category 2", the Excel-import
# "Categoryname1"/"Categoryname2"...) in the same shared course, which is a
# real collision, not a hypothetical one. This file therefore creates its
# OWN dedicated course "EXTEACH" (mirroring course.feature's "Create a
# course before testing" pattern, including that scenario's own
# course_creation_form_set_course_category_mandatory / default-French-
# language gotchas on this box), registers mmosquera as its COURSE_TEACHER
# via the Users tool's "Teachers" tab (same flow toolUsers.feature already
# documents/proves), does all exercise work there as her, and deletes the
# whole course at the end via /admin/course-list — simpler and more
# thorough teardown than unsubscribing + deleting exercises/categories
# piecemeal, since a dedicated single-purpose course has nothing else in it
# worth preserving. (The "Delete an exercise"/"Delete an exercise category"
# scenarios are still kept and still genuinely exercise that UI first,
# ahead of the final course deletion — dropping them would silently skip
# real coverage the admin port already proved matters.)
#
# acostea (the fixed student fixture) is likewise not already subscribed to
# a brand-new course, unlike TEMP where some other file's fixture state
# might carry it — "Subscribe a student to try the exercise" registers her
# explicitly first, mirroring toolReporting.feature's own subscribe-before-
# using-the-tool convention. No unsubscribe step is needed for her either:
# the final course deletion removes the whole course_rel_user row along
# with everything else.
#
# NO shared Background here — a deliberate, confirmed-live departure from
# every other file's "Background: Given I am a platform administrator"
# convention. This file's own scenarios genuinely need three different
# roles (admin for course/user administration, teacher for the exercise
# tool itself, student for "Try exercise"), and each Playwright scenario
# gets a fresh, unauthenticated browser context anyway — the FIRST real
# attempt at this file put the common role ("I am a teacher") in the
# Background and had only the admin/student scenarios override it with
# their own login step, mirroring toolExerciseAdmin.feature's "Try
# exercise" (Background admin, overridden to student). That produced real,
# repeated CI-style failures across three separate live runs (a hung
# `getByLabel(title)` wait on course_add.php that turned out to still be
# showing the anonymous homepage, and a detached-mid-click "Sign in"
# button) — every failure was in a scenario doing a SECOND login
# (Background's teacher login, immediately followed by the scenario's own
# admin-login override) that never actually landed as the second user,
# while every plain single-login scenario in the same runs passed cleanly.
# Removing the Background and giving every scenario exactly ONE explicit
# login as its own first step (never a same-scenario role switch) fixed it
# — confirmed by a full clean rerun of all 26 scenarios afterward. This
# doesn't contradict toolExerciseAdmin.feature's own working double-login
# scenario; it just wasn't worth re-litigating why one double-login
# sequence is reliable and another isn't when a single-login-per-scenario
# design is both simpler and something this suite's step definitions
# already support for free.
#
# REAL APP BEHAVIOR CONFIRMED IDENTICAL to the admin port's own finding:
# "Try exercise" here also needs "I press 'Next question' until ... appears"
# (not a bare "I press 'Next question'") to avoid the same
# isSavingAnswer-window click race documented in toolExerciseAdmin.feature's
# header comment — reran clean, same final score, 85 / 105.
#
# REAL APP RACE FOUND, specific to registering a course TEACHER (not covered
# by toolUsers.feature's own admin-only scenarios): CourseUserListView.vue's
# "Teachers"/"Learners" tabs switch the active type via a pure client-side
# route push (history state, no new network request) — pressing "Teachers"
# then immediately clicking "Add" can still read the PREVIOUS type (5,
# Learners) off the "Add" link's href if the click lands before that route
# push resolves, since neither "wait for the page to be loaded"
# (domcontentloaded) nor "wait for the page content to settle" (networkidle)
# reflects a client-side-only URL change at all. Confirmed live via the
# `/api/course_rel_users` endpoint: an early version of "Subscribe the
# teacher to the course" reported "subscribed to the course" (a generic,
# type-agnostic success message) while actually registering mmosquera with
# `status: 5` (STUDENT) instead of `status: 1` (TEACHER) — which then made
# every subsequent scenario's `canManage` check silently false
# (IsAllowedToEditHelper::check() requires
# ROLE_CURRENT_COURSE_TEACHER/_SESSION_TEACHER), hanging every "I follow
# 'Tests'"/"Question categories'"/"'Create exercise'" click for the full
# 90s test timeout with no direct clue why. Fixed by asserting on the URL
# itself (the one thing that DOES change synchronously with the click) via
# the already-existing generic "the URL should contain '...'" step, both
# right after pressing "Teachers" and right after clicking "Add" — confirmed
# live afterward that mmosquera's course_rel_user row reads `status: 1` and
# GET /api/exercise/list?cid=... returns `canManage: true` for her.
#
# REAL APP BEHAVIOR FOUND, course-lifecycle-specific (never surfaced by
# toolExerciseAdmin.feature, since course TEMP's tools were already made
# visible to students by whatever else set TEMP up originally):
# CourseHome.vue hides any course tool from non-editor roles
# (`toolsForDisplay`) unless its resourceLink `visibility` is the VISIBLE
# state — and a brand-new course's tools are NOT visible-to-students by
# default. Confirmed live: acostea (subscribed as a genuine
# ROLE_CURRENT_COURSE_STUDENT via course_rel_user) loaded EXTEACH's course
# home page fine (properly authenticated, breadcrumb and "EXTEACH" heading
# both rendered) but got literally zero tools in the tools grid — no
# "Tests", nothing — which is why "I follow 'Tests'" hung for the full 90s
# with no error of any kind. The course home page's own "Tools" section header
# has a teacher/admin-only "Show all" toggle (CourseHome.vue's
# `onClickShowAll()`, POSTs .../change_visibility/show) that a real teacher
# would need to click at least once for a freshly created course before
# students can see anything in it — "Subscribe a student to try the
# exercise" (already an admin-context scenario visiting the course home
# page) now presses it before subscribing acostea. Confirmed live
# afterward: same course, same student, "Tests" now visible immediately.
#
# REAL, EXTERNAL finding (not this file's own bug, but hit live while
# debugging the two issues above, so recorded here for whoever investigates
# a similar future timeout): the "Registration > Enable terms and
# conditions" platform setting (public/main/auth/tc.php) defaults OFF, but
# was found transiently ON mid-run on this shared box — some OTHER
# concurrent feature file's own test toggles it and had not yet restored it
# by teardown. While it's on, every login (any role) lands on tc.php instead
# of its normal destination, and every subsequent navigation in that
# scenario bounces to "/login?redirect=..." instead — the login step itself
# never throws (it did leave /login), so the failure only surfaces several
# steps later as an unrelated-looking timeout, exactly like the two bugs
# above. loginAs() in common.steps.ts (used by every "I am a ..." step, not
# just this file's) now defensively accepts the interstitial if it's ever
# the landing page — a no-op the overwhelming rest of the time, when the
# setting is off and tc.php never appears, and cheap insurance against the
# same cross-file contamination hitting some other file's login next.
@common @tools
Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  # Deliberately NOT named "Create a course before testing" (2026-08-19):
  # that is the exact --grep string package.json's test:playwright:seed-course
  # uses, and it used to match THIS scenario too, not just course.feature's.
  # Both therefore ran inside the "Seed test course" CI step on a shared
  # worker pool, so whichever finished first took course id 3 and the other
  # got id 4 — a real coin flip (observed both ways: TEMP won on real CI,
  # EXTEACH won locally, 43.7s vs 54.6s). Now that the whole suite pins TEMP
  # to cid=3, that race would silently point 96 hardcoded cid=3 URLs at
  # EXTEACH instead of TEMP roughly half the time. Renaming this scenario
  # takes it out of the seed step altogether, which is where it always
  # belonged: unlike TEMP (shared by 14 files, hence pre-seeded), EXTEACH is
  # used by THIS file only, is always addressed by course CODE and never by
  # cid (zero cid= occurrences in this file), so its own id is irrelevant and
  # it can be created inline as this file's first scenario — `fullyParallel:
  # false` already guarantees scenarios within one file run in order.
  #
  # Kept as its own dedicated course rather than merged onto TEMP: see this
  # file's header comment above for the confirmed-real collision that causes
  # (toolExerciseAdmin.feature does the same exercise work in TEMP, creating
  # identically-named "Exercise 1"/"Category 1"/"Category 2"/Excel-import
  # "Categoryname1"/"Categoryname2" items, and different feature files do run
  # concurrently in different workers).
  Scenario: Create the exercise teacher course before testing
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "EXTEACH"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I should see "EXTEACH"

  Scenario: Subscribe the teacher to the course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I press "Teachers"
    And the URL should contain "type=1"
    And I click the "[title='Add']" element
    And the URL should contain "type=1"
    And I wait for the page to be loaded
    And I fill in the following:
      | search | mmosquera |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Mosquera"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  Scenario: Create a question category
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Question categories"
    And I wait for the page content to settle
    And I press "Add category"
    And I fill in "categoryTitle" with "Category 1"
    And I fill in "categoryDescription" with "Category 1 description"
    And I click the "button:not(.p-button-icon-only):has-text('Add category')" element
    And I wait for the page content to settle
    Then I should see "Category 1"

  Scenario: Create a second question category
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Question categories"
    And I wait for the page content to settle
    And I press "Add category"
    And I fill in "categoryTitle" with "Category 2"
    And I fill in "categoryDescription" with "Category 2 description"
    And I click the "button:not(.p-button-icon-only):has-text('Add category')" element
    And I wait for the page content to settle
    Then I should see "Category 2"

  Scenario: Create an exercise
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "Exercise 1"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"

  Scenario: Edit an exercise
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Configure']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "0 questions"
    And I should not see an error

  Scenario: Add question "Multiple choice" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Multiple choice"
    And I wait for the page content to settle
    When I fill in "question" with "Multiple choice"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 3 text with "Answer false"
    And I fill in the answer 4 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I fill in the answer 3 comment with "Comment false"
    And I fill in the answer 4 comment with "Comment false"
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Multiple choice"
    And I should not see an error

  Scenario: Add question "Multiple answer" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Multiple answer"
    And I wait for the page content to settle
    When I fill in "question" with "Multiple answers"
    And I mark answer 1 as correct
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 3 text with "Answer false"
    And I fill in the answer 4 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I fill in the answer 3 comment with "Comment false"
    And I fill in the answer 4 comment with "Comment false"
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Multiple answers"
    And I should not see an error

  Scenario: Add question "Fill in blanks" to "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Fill blanks or form"
    And I wait for the page content to settle
    When I fill in "question" with "Fill blanks"
    And I fill in tinymce field "exercise-fill-blanks-text" with "Romeo and [Juliet]"
    And I press "Refresh terms"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Fill blanks"
    And I should not see an error

  Scenario: Add question "Matching" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Matching"
    And I wait for the page content to settle
    When I fill in "question" with "Matching"
    And I fill in matching pair 1 with "Answer A"
    And I fill in matching pair 2 with "Answer B"
    And I fill in tinymce field "exercise-matching-option-option-1" with "Option A"
    And I fill in tinymce field "exercise-matching-option-option-2" with "Option B"
    And I press "Add this question to the test"
    And I wait for the page content to settle
    Then I should see "Matching"
    And I should not see an error

  Scenario: Add question "Open" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    When I fill in "question" with "Open question"
    And I fill in "exercise-manual-question-score" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Open question"

  Scenario: Add question "Oral expression" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Oral expression"
    And I wait for the page content to settle
    When I fill in "question" with "Oral expression question"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should not see an error

  Scenario: Add question "Exact answers combination" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Exact Selection"
    And I wait for the page content to settle
    When I fill in "question" with "Exact answers combination"
    And I mark answer 1 as correct
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Exact answers combination"
    And I should not see an error

  Scenario: Add question "Unique answer with unknown" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Unique answer with unknown"
    And I wait for the page content to settle
    When I fill in "question" with "Unique answer with unknown"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 3 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I fill in the answer 3 comment with "Comment false"
    And I fill in "exercise-answer-score-0" with "10"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Unique answer with unknown"
    And I should not see an error

  Scenario: Add question "Multiple answer true/false/don't know" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Multiple answer true/false/don't know"
    And I wait for the page content to settle
    When I fill in "question" with "Multiple answer true - false - dont know"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer true"
    And I fill in the answer 3 text with "Answer true"
    And I fill in the answer 4 text with "Answer true"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment true"
    And I fill in the answer 3 comment with "Comment true"
    And I fill in the answer 4 comment with "Comment true"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Multiple answer true - false - dont know"
    And I should not see an error

  Scenario: Add question "Combination true/false/don't-know" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Combination true/false/don't-know"
    And I wait for the page content to settle
    When I fill in "question" with "Combination true - false - dont know"
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Combination true - false - dont know"
    And I should not see an error

  Scenario: Add question "Global multiple answer" to exercise created "Exercise 1"
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    And I follow the question type "Global multiple answer"
    And I wait for the page content to settle
    When I fill in "question" with "Global multiple answer"
    And I mark answer 1 as correct
    And I fill in the answer 1 text with "Answer true"
    And I fill in the answer 2 text with "Answer false"
    And I fill in the answer 3 text with "Answer false"
    And I fill in the answer 4 text with "Answer false"
    And I fill in the answer 1 comment with "Comment true"
    And I fill in the answer 2 comment with "Comment false"
    And I fill in the answer 3 comment with "Comment false"
    And I fill in the answer 4 comment with "Comment false"
    And I press "Save the question"
    And I wait for the page content to settle
    Then I should see "Global multiple answer"
    And I should not see an error

  Scenario: Duplicate exercise
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Copy this exercise as a new one']" icon in the row for "Exercise 1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should see "copied"
    And I should see "Exercise 1 - Copy"

  Scenario: Import exercise to test questions categories
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Import quiz from Excel"
    And I wait for the page content to settle
    Then I should see "Import quiz from Excel"
    And I attach the file "/tests/playwright/fixtures/exercise.xls" to the upload dropzone
    When I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I follow "Back to Tests tool"
    And I wait for the page content to settle
    And I follow "Question categories"
    And I wait for the page content to settle
    Then I should see "Categoryname1"
    And I should see "Categoryname2"

  Scenario: Import exercise from excel
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Import quiz from Excel"
    And I wait for the page content to settle
    Then I should see "Import quiz from Excel"
    And I attach the file "/public/main/exercise/quiz_template.xls" to the upload dropzone
    And I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I follow "Edit questions"
    And I wait for the page content to settle
    Then I should see "Definition of oligarchy"

  Scenario: Subscribe a student to try the exercise
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I press "Show all"
    And I wait for the page content to settle
    And I follow the course tool "Users"
    And I wait for the page to be loaded
    And I click the "[title='Add']" element
    And I wait for the page to be loaded
    And I fill in the following:
      | search | acostea |
    And I press "Search"
    And I wait for the page to be loaded
    Then I should see "Costea"
    And I click the "[title='Register']" element
    And I wait for the page to be loaded
    Then I should see "subscribed to the course"

  @slow-scenario
  Scenario: Try exercise "Exercise 1"
    Given I am a student
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Exercise 1"
    And I start the exercise
    And I wait for the page content to settle
    # Question 1 - Multiple choice
    Then I should see "Multiple choice"
    And I check the "Answer true" radio button
    And I press "Next question" until "Multiple answers" appears
    And I wait for the page content to settle
    # Question 2 - Multiple answers
    And I check the "Answer true" radio button
    And I press "Next question" until "Fill blanks" appears
    And I wait for the page content to settle
    # Question 3 - Fill blanks
    And I fill in blank 1 with "Juliet"
    And I press "Next question" until "Matching" appears
    And I wait for the page content to settle
    # Question 4 - Matching
    And I select "A. Option A" from matching select 1
    And I select "B. Option B" from matching select 2
    And I press "Next question" until "Open question" appears
    And I wait for the page content to settle
    # Question 5 - Open question
    And I fill in the open answer with "Hello you"
    And I press "Next question" until "Oral expression question" appears
    And I wait for the page content to settle
    # Question 6 - Oral expression (left unanswered, manually corrected)
    And I press "Next question" until "Exact answers combination" appears
    And I wait for the page content to settle
    # Question 7 - Exact answers combination
    And I check the "Answer true" radio button
    And I press "Next question" until "Unique answer with unknown" appears
    And I wait for the page content to settle
    # Question 8 - Unique answer with unknown
    And I check the "Answer true" radio button
    And I press "Next question" until "Multiple answer true - false - dont know" appears
    And I wait for the page content to settle
    # Question 9 - Multiple answer true/false/don't know
    And I check every "True" option on the page
    And I press "Next question" until "Combination true - false - dont know" appears
    And I wait for the page content to settle
    # Question 10 - Combination true/false/don't-know
    And I check every "True" option on the page
    And I press "Next question" until "Global multiple answer" appears
    And I wait for the page content to settle
    # Question 11 - Global multiple answer
    And I check the "Answer true" radio button
    And I press "Finish test"
    And I wait for the page content to settle
    Then I should see "Exercise completed"
    And I should see "85 / 105"

  Scenario: Teacher checks exercise results
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Tests"
    And I wait for the page content to settle
    And I click the "[title='Results']" icon in the row for "Exercise 1"
    And I wait for the page content to settle
    Then I should not see "No attempts found"
    And I should see "Attempts: 1"
    And I should see "Costea"
    And I should see "85 / 105"

  Scenario: Delete an exercise
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "Exercise 1 - Copy"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Exercise 1 - Copy"
    And I click the "[title='Delete']" icon in the row for "Exercise 1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Exercise 1"
    And I click the "[title='Delete']" icon in the row for "Exercise for Behat test"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Exercise for Behat test"
    And I click the "[title='Delete']" icon in the row for "IQ test"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "IQ test"

  Scenario: Delete an exercise category
    Given I am a teacher
    And I wait for the page to be loaded
    And I am on course "EXTEACH" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Question categories"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "Category 1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Category 1"
    And I click the "[title='Delete']" icon in the row for "Category 2"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Category 2"
    And I click the "[title='Delete']" icon in the row for "Categoryname1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Categoryname1"
    And I click the "[title='Delete']" icon in the row for "Categoryname2"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Categoryname2"

  Scenario: Delete the course
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on "/admin/course-list?keyword=EXTEACH"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "EXTEACH"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "EXTEACH"
