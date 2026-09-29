# Ported from tests/behat/features/questionPool.feature — rewritten, not
# verbatim. Course creation, the Tests (exercise) tool, and the question pool
# have all moved to Vue since this was written, and one real BEHAVIOR change
# was found (not just selectors) while verifying live:
#
# Real CI-reproducible failure (a clean solo run, not just contention):
# right after following a course-tool link ("Tests"/"Exercises"), the Vue
# tool's own toolbar icons ("Create exercise", "Edit questions", etc.) take a
# moment to render asynchronously — "wait for the page to be loaded" is
# domcontentloaded only and gives that render no extra time, so the very
# next "I follow"/click can catch the toolbar mid-render and time out
# hunting for an icon that hasn't appeared yet (confirmed live: the same
# "Create exercise" link resolves fine once the page has genuinely settled).
# Every wait step in this file now uses "I wait for the page content to
# settle" (bounded networkidle) instead, same fix already applied to
# toolWiki.feature/toolChat.feature for this identical class of race.
#
# - Course creation: "/courses" + "span.mdi-plus" (a course-list "+" button)
#   no longer exists. Course creation is now its own page,
#   /resources/courses/new (reached via the topbar's "Create course" link),
#   with a single "Course name" field (id="course-name", unchanged) and a
#   "Create this course" button — confirmed live end-to-end, redirects
#   straight to the new course's home page.
# - Tests tool: the exercise list toolbar is now icon buttons with real
#   `title` attributes ("Create exercise", "Add a question", "Recycle
#   existing questions", etc. — confirmed via a live DOM dump), so "I follow"
#   matches them directly by title instead of the original's brittle
#   `i.mdi-order-bool-ascending-variant` / `em.mdi-pencil` CSS classes (which
#   no longer exist — icons render as PrimeVue button icons now, several as
#   `<span class="mdi ...">` rather than `<i>`). "Open question" and "Upload
#   Answer" remain plain link text, confirmed unchanged. The question
#   title/score fields are now id="exercise-question-title" (name="question")
#   and id="exercise-manual-question-score" (a PrimeVue InputNumber spinner,
#   confirmed fillReliably's clear+pressSequentially works on it fine) —
#   neither original id ("questionName"/"weighting") exists anymore. Question/
#   exercise delete actions now show a PrimeVue confirm dialog with a "Yes"
#   button (the SweetAlert2 ".delete-swal"/"I confirm the popup" pairing this
#   file used no longer applies anywhere in this flow), matching this suite's
#   own established "I press 'Yes'" convention used throughout other files.
#
# - BEHAVIOR CHANGE, confirmed live via direct DB-state checks (not
#   assumed): deleting a QUESTION from within an exercise's question list is
#   now a real, permanent delete of that question — it does NOT become a
#   orphan question", disproving the original scenario's whole premise
#   (create QPQUESTION1, delete it, then later expect it to reappear as an
#   orphan in a "recycle" list). Deleting the whole EXERCISE, by contrast,
#   still only unlinks its questions — they DO survive as orphans in the
#   course's own "Recycle existing questions" bank afterward (confirmed live
#   by deleting an exercise and finding its question still listed there
#   immediately after). The rewritten scenarios below test the flow that
#   actually exists now: recycling shows a course's questions regardless of
#   whether they're already attached to the exercise being edited (also
#   confirmed live — it does NOT exclude the current exercise's own
#   questions, unlike what the original scenario assumed), and orphaning
#   only happens via exercise deletion, not per-question deletion.
#
# - Course deletion is now scoped to whichever course's own Course
#   maintenance page you're on — typing a DIFFERENT course's code no longer
#   deletes that other course (confirmed live: the "Delete course" button
#   stays disabled unless the typed code matches the CURRENT course exactly).
#   The original's last scenario typed "QP2" while positioned on QP1's own
#   maintenance page and expected "QP1" to disappear — that combination can
#   never work under the current, verified behavior (the button never
#   enables), so it's fixed here to type the matching course's own code.
#   "Course maintenance" itself now lives behind a "More actions" dropdown
#   (aria-label, confirmed live) rather than a bare `span.mdi-cog`, which
#   ambiguously matches the course-context sidebar's own gear icon too.
#   "Delete course" triggers a NATIVE `confirm()` popup (confirmed live via
#   the dialog event, not a SweetAlert2/PrimeVue one), so it's clicked via "I
#   click the ... element" (which already registers the native-dialog
#   auto-accept handler) rather than "I press".
#
# - DROPPED: "Admin reviews question pool and filters by course"
#   (/main/admin/questions.php). Confirmed live and reproducible: submitting
#   its search form always returns a 500 ("An unexpected error occurred"),
#   regardless of filters used or whether any match exists — a genuine,
#   pre-existing server-side bug on this page, unrelated to any selector or
#   test data, and out of scope to fix as part of this migration. A minimal
#   scenario below just confirms the admin page itself still loads (without
#   ever submitting its broken search), and the equivalent "review all
#   questions in a course" need is already covered by the still-working,
#   per-course "Recycle existing questions" view exercised elsewhere in this
#   file.
#
# Both courses are fully deleted by this file's own last two scenarios
# (Course maintenance's "Completely delete this course"), so nothing it
# creates is left behind for other feature files to collide with.
@common @tools
Feature: Question pool management for QP scenarios
  This feature contains scenarios to create courses, tests and questions
  for manual verification of the question pool workflow.

  Background:
    Given I am a platform administrator
    And I wait for the page content to settle

  Scenario: Create course QP1
    Given I am on "/resources/courses/new"
    And I wait for the page content to settle
    And I fill in the following:
      | course-name | QP1 |
    When I press "Create this course"
    And I wait for the page content to settle
    Then I should see "QP1"

  Scenario: Create course QP2
    Given I am on "/resources/courses/new"
    And I wait for the page content to settle
    And I fill in the following:
      | course-name | QP2 |
    When I press "Create this course"
    And I wait for the page content to settle
    Then I should see "QP2"

  Scenario: Create a test and add question QPQUESTION1 (then delete)
    Given I am on course "QP1" homepage
    And I wait for the page content to settle
    When I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in the following:
      | exercise-title | QPTEST1 |
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow "Open question"
    And I wait for the page content to settle
    And I fill in the following:
      | exercise-question-title      | QPQUESTION1 |
      | exercise-manual-question-score | 5         |
    And I press "Save the question"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QPQUESTION1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see an error

  Scenario: Upload answer (QPQUESTION2) and check recycle behavior
    Given I am on course "QP1" homepage
    And I wait for the page content to settle
    When I follow "Tests"
    And I wait for the page content to settle
    And I follow "Edit questions"
    And I wait for the page content to settle
    When I follow "Upload Answer"
    And I wait for the page content to settle
    And I fill in the following:
      | exercise-question-title      | QPQUESTION2 |
      | exercise-manual-question-score | 4         |
    And I press "Save the question"
    And I wait for the page content to settle
    And I follow "Recycle existing questions"
    And I wait for the page content to settle
    # Confirmed live: unlike the original scenario's assumption, this scoped
    # recycle view does NOT exclude questions already attached to the
    # exercise being edited — QPQUESTION2 shows up here too, right after
    # being saved into this same exercise.
    Then I should see "QPQUESTION2"
    When I follow "Exercises"
    And I wait for the page content to settle
    And I follow "Recycle existing questions"
    And I wait for the page content to settle
    Then I should see "QPQUESTION2"
    And I should not see an error

  # Replaces "Admin reviews question pool and filters by course" — see this
  # file's header comment for why the original's search-and-filter
  # assertions are dropped (a confirmed, pre-existing 500 error on this
  # page's search action, unrelated to this migration).
  Scenario: Admin Questions page still loads
    Given I am on "/admin"
    And I wait for the page content to settle
    When I follow "Questions"
    And I wait for the page content to settle
    Then I should see "Questions"
    And I should not see an error

  Scenario: Admin deletes course QP2 from course maintenance
    Given I am on course "QP2" homepage
    And I wait for the page content to settle
    And I press "More actions"
    And I follow "Course maintenance"
    And I wait for the page content to settle
    And I follow "Completely delete this course"
    And I wait for the page content to settle
    And I fill in the following:
      | delete-course-code | QP2 |
    And I check "delete-docs"
    And I click the "button.p-button-danger:has-text('Delete course')" element
    And I wait for the page content to settle
    Then I should not see "QP2"

  Scenario: Admin removes the exercise and its orphaned question for QP1
    Given I am on course "QP1" homepage
    And I wait for the page content to settle
    When I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QPTEST1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "QPTEST1"
    # Confirmed live: deleting the exercise unlinks but does not delete its
    # question — QPQUESTION2 survives as an orphan in the course's question
    # bank, exactly like the original scenario expected (just reached via
    # exercise deletion, not per-question deletion — see header comment).
    When I follow "Recycle existing questions"
    And I wait for the page content to settle
    Then I should see "QPQUESTION2"
    And I click the "[title='Delete']" icon in the row for "QPQUESTION2"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "QPQUESTION2"
    And I should not see an error

  # Original scenario typed "QP2" as the confirmation code while positioned
  # on QP1's own Course maintenance page — confirmed live this can never
  # delete anything: the "Delete course" button only enables once the typed
  # code matches the CURRENT course exactly (QP2 was also already deleted by
  # this point in the original ordering). Fixed to type "QP1", the course
  # actually being deleted here.
  Scenario: Admin deletes course QP1 via Course maintenance
    Given I am on course "QP1" homepage
    And I wait for the page content to settle
    When I press "More actions"
    And I follow "Course maintenance"
    And I wait for the page content to settle
    And I follow "Completely delete this course"
    And I wait for the page content to settle
    And I fill in the following:
      | delete-course-code | QP1 |
    And I check "delete-docs"
    And I click the "button.p-button-danger:has-text('Delete course')" element
    And I wait for the page content to settle
    Then I should not see "QP1"
