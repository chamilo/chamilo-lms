# Ported from tests/behat/features/toolExerciseAdmin.feature — rewritten, not
# verbatim. The exercise/quiz tool has been fully migrated to Vue since the
# original was written (assets/vue/views/exercise/*.vue,
# assets/vue/router/exercise.js, /resources/exercise/...): none of the
# original's legacy selectors survive ("i.mdi-pencil"/"i.mdi-cog" icon
# selectors, "weighting[N]"/"correct[N]"/"answerN"/"commentN" legacy field
# ids/names, bootstrap-select "matches_2", the CKEditor4-only "I fill the
# only ckeditor in the page with" step). Confirmed live end-to-end for every
# scenario kept below (created/edited an exercise, added all 11 question
# types the original covered, duplicated it, imported both fixture .xls
# files, took the exercise as a student, and viewed the teacher-side report)
# — see the new step definitions this file needed in common.steps.ts
# ("I follow the question type ...", "I fill in the answer/comment ...",
# "I mark answer ... as correct", "I fill in matching pair ...", the
# player-only "I select ... from matching select ...", "I fill in blank ...
# with ...", "I fill in the open answer with ...", "I check every ...
# option on the page") for exactly why each was needed instead of an
# existing generic step.
#
# Three scenarios from the original are DROPPED, not just rewritten:
# - "Check exercise result": the original's actual behavior (re-answering a
#   DIFFERENT set of questions — "semantics"/"RNASL"/choice_id_6_*/etc. —
#   under a "Results and feedback" link) never matched this file's own
#   question set even in the original Behat suite; it reads like a stale
#   copy-paste from a different exercise entirely. The new Vue "Learner
#   score" report page (ExerciseReportView.vue) is a read-only attempts
#   table, not an interactive question flow — "Teacher checks exercise
#   results" below covers the genuine equivalent (the report lists the
#   attempt with its computed score).
# - "Teacher looks at exercise results by categories" and "Delete session":
#   both depend on a session literally named "Session Exercise" that no
#   scenario in this file (or its original Behat version) ever creates —
#   it's assumed to already exist, planted by some other, unrelated feature
#   file. That cross-file coupling is exactly the class of bug this
#   migration keeps finding and fixing elsewhere (see toolGlossary.feature's
#   missing LP teardown breaking an unrelated file this same session) — not
#   a pattern to carry forward. A self-contained rewrite would need its own
#   "Create a session" scenario, which is out of scope for an exercise-tool
#   port.
#
# Cleanup: every exercise and question category this file's own scenarios
# create is deleted by "Delete an exercise"/"Delete an exercise category"
# at the end (including the exercise created by "Duplicate exercise" and
# the two categories ("Categoryname1"/"Categoryname2") that come along for
# free as a side effect of the Excel import, confirmed live — neither the
# original Behat file nor a naive port would have caught the Copy leak,
# since the original "Delete an exercise" scenario never deletes it
# either). No platform SETTING is touched anywhere in this file, so no
# registerSettingsGuard() tag is needed.
#
# Drops "I zoom out to maximum" (never used anyway) — see
# adminHealthBlock.feature's header comment for why zoom steps are dropped
# throughout this migration.
#
# REAL APP RACE FOUND, confirmed live (not just a flaky rerun): "Try exercise" scored
# 65/105 instead of 105/105's-worth-of-answered-questions, on the exact same two
# questions every time ("Multiple answers", "Unique answer with unknown"). A captured
# network trace showed both submitted an EMPTY answer — `{"choices":[]}` /
# `{"choice":null}` — even though "I check the ... radio button" reported no error.
# ExercisePlayerView.vue's "Next question" button is only disabled while
# `isSavingAnswer` is true, so a click landing right as the PREVIOUS question's
# answer-save request resolves can be silently swallowed — the visible question stays
# on the SAME one, so the very next scripted answer step lands on the wrong question
# and its own answer never gets submitted. "I press 'Next question' until ... appears"
# (common.steps.ts) re-clicks until the next question's own title heading actually
# shows up, which is what confirms the click landed — this alone fixes it, verified via
# a full clean rerun scoring 85/105.
@common @tools
Feature: Exercise tool
  In order to use the exercise tool
  The teachers should be able to create exercises

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Create a question category
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Copy this exercise as a new one']" icon in the row for "Exercise 1"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should see "copied"
    And I should see "Exercise 1 - Copy"

  Scenario: Import exercise to test questions categories
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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

  @slow-scenario
  Scenario: Try exercise "Exercise 1"
    # TEMP has no students on a fresh install. course_user_registration.feature
    # also subscribes acostea, but that file runs in a different worker with
    # no ordering guarantee — a real CI run bounced the student to /login
    # with "You're not allowed in this course" before "I follow Tests".
    # Subscribe here so this scenario is self-contained; Register is a no-op
    # if she is already in the course.
    Given I am a platform administrator
    And I am on "/main/user/subscribe_user.php?keyword=acostea&type=5&cid=3"
    And wait for the page to be loaded
    Then I follow "Register" if it is visible
    And wait for the page to be loaded
    Given I am not logged
    And I am a student
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow the course tool "Tests"
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
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
    Given I am on course "TEMP" homepage
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
