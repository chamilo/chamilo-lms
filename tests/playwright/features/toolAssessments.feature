# Ported from tests/behat/features/toolAssessments.feature — but only the
# scenarios genuinely belonging to the Assessments (gradebook) tool itself.
# The Behat original interleaved 4 duplicate/near-duplicate scenarios that
# actually belong to the Assignments tool ("Create a work", "Edit maximum
# score", "Send work as student (acostea)", "Add a comment and a
# attachment...") — those are toolAssignment.feature's own scenarios almost
# verbatim, and porting them a second time here would race
# tests/playwright/features/toolWork.feature (both files would create
# identically-titled "Work 1"/"Work 1 Test..." items in the same shared
# course TEMP, in different parallel workers, with no ordering guarantee).
# That cross-file coupling is exactly the class of bug this migration keeps
# finding and fixing elsewhere (toolGlossary's missing LP teardown breaking
# toolLp; toolExerciseAdmin/toolExerciseTeacher's shared-session
# assumption) — not a pattern to carry forward. EXCLUDED here entirely; see
# toolWork.feature's own header comment for where those 4 scenarios'
# genuine equivalents now live.
#
# 2026-08-20 REAL CI FAILURE: the gradebook tool IS Vue now
# (assets/vue/views/gradebook/GradebookListView.vue, route
# /resources/gradebook/:node/). The Behat-era `i.mdi-pencil` /
# `i.mdi-table-plus` / `i.mdi-account` / `i.mdi-format-list-text`
# selectors match nothing on the live page — BaseButton renders
# icon-only controls as <button aria-label="..."> / <a title="...">
# with an inner span.mdi, never a bare <i class="mdi-...">. A real CI
# snapshot of every failing scenario in this file showed the Vue
# toolbar ("Add a category" / "Add classroom activity" / "Add online
# activity" / "Edit") and an empty table, then a 90s hang waiting for
# those legacy <i> tags. Rewritten against the live Vue dialogs and
# named fields (gradebook-category-certificate-min-score,
# gradebook-evaluation-title, etc.). The @skip'd scenarios below still
# describe the old PHP pages and are left skipped.
#
# SELF-CONTAINMENT: course TEMP has ZERO subscribed learners on this box by
# default (confirmed live: /api/course_rel_users?course=/api/courses/1
# returns only the admin's own row) — the gradebook's own "grade learners"
# step has nothing to list without one. Subscribes "norizales" as a student
# in the very first scenario (reusing the exact flow already proven by
# tests/playwright/features/course_user_registration.feature's own
# "Subscribe ... as student" scenario, not reinvented) and unsubscribes her
# in the very last one — this file must not depend on
# course_user_registration.feature having already run (that file's own
# comment says it leaves users "subscribed for further tests", i.e. it
# assumes persistent shared state across files, which this migration's own
# self-containment rule explicitly rejects).
#
# REAL CI RACE FOUND: this file originally used the shared "acostea" fixture
# for the exact same subscribe-use-unsubscribe round trip. toolReporting.
# feature does the identical thing to the identical (acostea, TEMP) pairing,
# and `fullyParallel: false` only serializes scenarios WITHIN one file —
# different files still run concurrently across workers (see playwright.
# config.ts's own comment on that distinction). A real CI run showed this
# file's very first scenario failing to find "Andrea" in the subscribe
# picker at all — subscribe_user.php's picker excludes users already
# subscribed to the course (`cu.user_id IS NULL` in get_user_data(), read in
# public/main/user/subscribe_user.php) — which is exactly what happens if
# toolReporting.feature (or any other file that leaves acostea subscribed
# and never tears her down: toolWork.feature, toolGroup.feature,
# toolChat.feature, toolExerciseTeacher.feature, course_user_registration.
# feature itself) had already subscribed her by the time this scenario ran.
# The same live run also showed a LATER scenario here ("Edit a result...")
# losing her row mid-file, consistent with toolReporting.feature's own
# teardown unsubscribing her concurrently while this file was still using
# her. Fixed by switching this file's own round trip to "norizales" (Noa
# Orizales, status 5/STUDENT in tests/datafiller/data_users.php) — a fixture
# confirmed unused by any other .feature file or step in this suite, so this
# file's own subscribe/use/unsubscribe cycle can never be raced by another
# file's independent management of the same user+course pairing.
# toolReporting.feature was given its own, different, equally-unused
# dedicated learner ("pperez") for the same reason — reusing the same
# replacement user in both files would just relocate the identical race.
#
# norizales's real numeric user id is NOT hardcoded (the Behat original's
# "score[5]" assumed id 5, which is not her id on this box — confirmed live
# via /api/users?username=norizales, and that number is not guaranteed
# stable across boxes/runs either). The "I fill in the score for ... with
# ..." step (added to common.steps.ts) looks her id up by username right
# before filling, exactly mirroring the existing "I have a friend named ..."
# step's own id-lookup-instead-of-hardcoding pattern elsewhere in this suite.
#
# Field/selector drift confirmed live vs the Behat original:
# - gradebook_add_eval.php: "evaluation_title"/"weight_mask"/
#   "add_eval_form_max"/"min_score"/"add_eval_form_submit" all still
#   correct. The "Grade learners" checkbox has no stable id (FormValidator
#   generates a fresh random `qf_<hash>` per render) — matched by its
#   visible label text instead ("I check 'Grade learners'").
# - gradebook_add_result.php: the per-learner field is genuinely
#   "score[<id>]" (id-based, not name-based — confirmed live), and
#   "add_result_form_submit" is correct.
# - gradebook_edit_cat.php: "edit_cat_form_certif_min_score" and
#   "edit_cat_form_submit" are both still correct. Its "Generate
#   certificates" checkbox (name="generate_certificates", also a random
#   `qf_<hash>` id) defaults to UNCHECKED on this box — REAL FINDING: the
#   "See list of learner certificates" toolbar icon (`i.mdi-format-list-
#   text`) never renders at all while this is off (confirmed by reading
#   public/main/gradebook/lib/fe/displaygradebook.php:588-591 — it's gated
#   behind `1 == $my_category['generate_certificates']`), so the Behat
#   original's own "Set certification minimum score" scenario — which
#   never touched this checkbox — could only have worked if this setting
#   already defaulted to checked on whatever box that suite last passed on.
#   Checking it explicitly here, in the same scenario, is a genuine
#   addition beyond a literal port, not an invented step.
# - gradebook_add_link.php: `#create_link_select_link`'s non-placeholder
#   options are all HARD-DISABLED server-side (`disabled="disabled"` in the
#   raw HTML) whenever course TEMP has zero items of that activity type —
#   confirmed live: "Assignments" only becomes selectable once a real
#   assignment exists in the course. This is WHY this scenario creates its
#   own dedicated "Assessment Link Work" assignment first (distinctly named
#   to avoid any collision with toolWork.feature's "Work 1 Test Edited" if
#   both files' suites ever run concurrently against the same course). Once
#   selected, `#add_link_select_link`'s own dropdown lists only the real
#   assignments that exist in the course (confirmed live: exactly one
#   option, "Assessment Link Work", when only one exists) — so no
#   assumption about "linking a specific one out of several" was needed.
#   `weight_mask`/`min_score`/`add_link_submit` are all correct.
# - gradebook_edit_link.php (reached via the "Edit weight" icon — REAL
#   DRIFT: title is on the child `<i>`, not the `<a>`, so plain "I follow"
#   can't find it; reused the existing row-scoped "I click the ... icon in
#   the row for ..." step instead of adding a new one): the submit button
#   is `edit_link_form_submit`, NOT `edit_eval_form_submit` as the Behat
#   original assumed (that id belongs to gradebook_edit_eval.php's own
#   form, a different page) — confirmed live. Flash text is "Assessment
#   edited", not "The evaluation has been successfully edited".
# - gradebook_view_result.php ("Edit a result"): REAL DISPLAY BUG found —
#   this page's own score cell mislabels the PERCENTAGE as if it were the
#   raw score (e.g. a raw score of 8/10 renders as "80" with "(80/10)"
#   underneath, nonsensically "80 out of 10"). The underlying stored value
#   is correct (confirmed by reopening the edit form immediately after:
#   the real field value is genuinely "8", not "80") — this is a rendering
#   bug in that one page only. Not the page this scenario asserts against:
#   gradebook_flatview.php (the "chart view"/List View reached via
#   `i.mdi-chart-box`) renders the SAME data correctly as "8 / 10", which
#   is what this scenario actually checks. Out of scope to fix here.
# - gradebook_display_certificate.php ("Open certificate..."): REAL FINDING
#   — `i.mdi-format-list-text` and `i.mdi-certificate` are NOT both on the
#   main Assessments toolbar as the Behat original's flat scenario implies.
#   `i.mdi-format-list-text` ("See list of learner certificates") IS on the
#   main toolbar and navigates here. Once here, THIS page has its own
#   SEPARATE `i.mdi-certificate` icon (title "Generate certificates",
#   confirmed via reading gradebook_display_certificate.php:393-396) that
#   must be clicked to actually generate the eligible learner's
#   certificate — nothing appears in the "No results available" list
#   before that, even though the learner's total score already clears the
#   minimum certification score. Once generated, a "Certificate" link
#   appears (`target="_blank"`, opens the actual rendered certificate HTML
#   in a new tab — manually confirmed live it shows "CERTIFICATE ... Noa
#   Orizales ... TEMP") — "I follow"/"I should see" both still work against
#   the ORIGINAL page/tab (the click never navigates the current page away,
#   by design), matching the Behat original's own equally simple assertion
#   shape; a full new-tab content assertion was judged unnecessary
#   complexity for what this scenario needs to prove.
# - The weight split below (exam weight 90, "Assessment Link Work" weight
#   10, left ungraded) is deliberate, not copied from the Behat original
#   (which used 50/50): the linked assignment is never actually submitted-
#   to/graded in this file (that's toolWork.feature's job for its own
#   dedicated item), so it always contributes 0 toward the learner's total.
#   90/10 keeps the total weight at the required 100 while still letting
#   the exam's own score alone (edited to 8/10 = 80%) clear a 50%
#   certification minimum (72% observed live) — the Behat original's 50/50
#   split would have left the learner under-scored for certification once
#   its own now-excluded assignment-grading scenario is taken out of the
#   picture.
# - "Deletes selected assessments": REAL FINDING — this triggers a genuine
#   native `confirm()` ("Please confirm your choice"), NOT the SweetAlert2
#   modal "I confirm the popup" was originally written for (that's this
#   SAME suite's OTHER delete flow, on /main/user/user.php's "Unsubscribe",
#   used at teardown below) — confirmed by attaching a raw dialog listener.
#   Uses the existing native-dialog-safe "I click the ... element" step
#   instead of "I follow" for this specific click (a `<span>`, not a real
#   link/button — "I follow"/"I press" alone would never attach the
#   listener in time for a synchronous native dialog). The Behat original's
#   `a.btn--action`/`button.justify-center` selectors don't exist anywhere
#   in the current markup — replaced with the real, visible "Select all" /
#   "Action" controls confirmed live.
#
# 2026-08-06 REAL CI FAILURE (3 new failures, same file, after the acostea ->
# norizales fixture fix above): "Link an Assignment..." hit `page.
# waitForLoadState` timing out at the full 90s test budget even though
# commit/domcontentloaded/load had already fired — i.e. the page itself had
# finished loading and something AFTER that hung. The only step in that
# scenario using unbounded `waitForLoadState("networkidle")` twice is "wait
# for the page to be loaded when ready" on the Vue-based Assignments tool
# (landing on the list after "I follow Assignments", and again right after
# "I press Save" creating "Assessment Link Work") — the exact same class of
# hang already found and fixed for other Vue SPA pages in this suite
# (toolGlossary's LP delete step, courseCatalogue.feature's catalogue-load
# race): this app's own persistent background polling can keep networkidle
# from ever resolving, and under the heavier concurrent load a real CI run
# sees vs. a clean local run, that occasionally exceeds even the 90s test
# timeout instead of merely running slow. Swapped both occurrences to the
# already-established bounded/tolerant "I wait for the page content to
# settle" step (domcontentloaded + a 10s-capped networkidle attempt) — same
# fix pattern, not a new one. Left the file's OTHER "wait for the page to be
# loaded when ready" calls (after "I follow Assessments", landing on the
# legacy PHP gradebook pages) unchanged: those are a different, non-Vue page
# and this exact step already runs there successfully in every other
# scenario in this file. A full, clean, non-concurrent local run of this file
# (10/10) could not reproduce the hang or the other 2 reported failures
# ("Edit a result...", "Deletes selected assessments") on either this fix or
# the pre-fix code — consistent with those 2 being downstream of scenario 4
# only when it actually times out, not an independent bug; left un-skipped
# since standalone runs pass reliably and the fix targets the one confirmed
# fragile step directly.
#
# Teardown leaves course TEMP exactly as found: the dedicated "Assessment
# Link Work" assignment deleted (its own gradebook LINK entry is removed by
# "Deletes selected assessments" above, but that never removes the
# underlying assignment item itself — confirmed live it survives that bulk
# delete and needs its own explicit deletion, same checkbox + "Delete
# selected" + "Yes" flow toolWork.feature's own delete scenario uses),
# certification minimum score restored to 75 (this box's real starting
# value — not a hardcoded assumed default) and "Generate certificates"
# unchecked again, and norizales unsubscribed from TEMP.
@common @tools
Feature: Assessments tool
  Manage assessment settings within a course

  Scenario: Subscribe a learner so the assessment tool has someone to grade
    Given I am a platform administrator
    And I am on "/main/user/subscribe_user.php?keyword=norizales&type=5&cid=3"
    And wait for the page to be loaded
    Then I should see "Noa"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Set certification minimum score to 50 in course TEMP
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I should see "Minimum certification score"
    Then I press "Edit"
    When I fill in the following:
      | gradebook-category-certificate-min-score | 50 |
    And I check "Generate certificates"
    And I press "Save"
    And I wait for the page content to settle
    Then I should see "50"

  Scenario: Create an evaluation "exam" in course TEMP
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I should see "Minimum certification score"
    Then I press "Add classroom activity"
    When I fill in the following:
      | gradebook-evaluation-title     | exam |
      | gradebook-evaluation-weight    | 90   |
      | gradebook-evaluation-max-score | 10   |
      | gradebook-evaluation-min-score | 3    |
    And I check "Grade learners"
    And I press "Add classroom activity"
    And I wait for the page content to settle
    When I fill in the score for "norizales" with "6"
    And I press "Save"
    And I wait for the page content to settle
    Then I should see "exam"

  # @skip 2026-08-06: recurring real-CI-only failure across multiple runs
  # (a wait-after-load hang was already fixed once for this exact scenario —
  # swapped an unbounded networkidle wait for the bounded "settle" step —
  # but it's still failing in real CI). Deferred per explicit user
  # instruction to stop re-chasing CI-only flakes with more runs.
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
  Scenario: Link an Assignment to the evaluation and edit its min score
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And I wait for the page content to settle
    Then I press "Create Assignment"
    And wait for the page to be loaded
    When I fill in the following:
      | Assignment name | Assessment Link Work |
    And I fill in the active tinymce editor with "Link target for the Assessments tool"
    And I press "Save"
    And I wait for the page content to settle
    Then I should see "Assignment created"
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait for the page to be loaded when ready
    # Tag-agnostic ".mdi-*", NOT "i.mdi-*": the Assessments tool is Vue now
    # (/resources/gradebook/<node>/) and its icons are rendered by BaseButton as
    # <span class="p-button-icon mdi mdi-...">, never <i>. Measured on the live
    # page: i.mdi-link-plus / i.mdi-pencil / i.mdi-chart-box all match ZERO
    # elements, while .mdi-link-plus matches 1 and .mdi-pencil matches 3, every
    # one of them a SPAN. The "i." prefix is a leftover from the legacy
    # displaygradebook.php era (which really did emit <i> via
    # Display::getMdiIcon), and because resolveField/click waits rather than
    # failing fast, it presented as a 90s timeout instead of "no such element".
    # Same fix already applied in sessionAccess.feature for the same reason.
    Then I click the ".mdi-link-plus" element
    And I wait for the page to be loaded
    # REWRITTEN 2026-08-23 against the live Vue "Add online activity" dialog.
    # Every identifier below was dumped from the real page; the previous ones
    # were all legacy leftovers that matched nothing:
    #   create_link_select_link -> #gradebook-link-type   (a PrimeVue combobox,
    #       NOT a native <select>, so "I select ... from ..." cannot drive it)
    #   weight_mask             -> #gradebook-link-weight
    #   min_score               -> #gradebook-link-min-score
    #   add_link_submit         -> the dialog's own "Add online activity" button
    #       (it reuses the toolbar label, same pattern as the evaluation dialog)
    #   [title='Edit weight']   -> [title='Edit']  (the row action is plain
    #       "Edit"; no "Edit weight" control exists any more)
    #   edit_link_form_submit   -> "Save" (the Edit dialog is header "Edit" with
    #       buttons ["Cancel", "Save"])
    #
    # A genuinely NEW step is also required: the form now has TWO comboboxes,
    # a Type and then a resource picker (#gradebook-link-resource), where the
    # old single select apparently sufficed. Verified live that Type offers
    # ["Assignments"] and the resource picker then offers
    # ["Assessment Link Work"], and that submitting adds the row.
    #
    # The two message assertions are replaced rather than retranslated: neither
    # "The link has been added" nor "Assessment edited" is rendered any more
    # (checked the page text after each submit — no add/edit confirmation text
    # at all). Asserting the resulting STATE is stronger than asserting a toast
    # that may not exist: the row must appear, and after editing, re-opening the
    # dialog must show the new minimum score actually persisted.
    And I click the "#gradebook-link-type" element
    And I click the "[role='option']:has-text('Assignments')" element
    And I wait for the page content to settle
    And I click the "#gradebook-link-resource" element
    And I click the "[role='option']:has-text('Assessment Link Work')" element
    When I fill in the following:
      | gradebook-link-weight    | 10 |
      | gradebook-link-min-score | 2  |
    And I press "Add online activity"
    And I wait for the page content to settle
    Then I should see "Assessment Link Work"
    Then I click the "[title='Edit']" icon in the row for "Assessment Link Work"
    And I wait for the page content to settle
    When I fill in the following:
      | gradebook-link-min-score | 3 |
    And I press "Save"
    And I wait for the page content to settle
    # Re-open the row's Edit dialog and read the value back, so this genuinely
    # proves the edit persisted instead of just proving the row still exists.
    Then I click the "[title='Edit']" icon in the row for "Assessment Link Work"
    And I wait for the page content to settle
    Then the field "gradebook-link-min-score" should have value "3"

  # @skip 2026-08-06: recurring real-CI-only failure across multiple runs.
  # A prior investigation found no deterministic bug (one clean local run
  # passed all 10/10 scenarios; other local runs showed signs of shared-box
  # contamination from concurrent activity, not a real defect in this
  # scenario's own logic). Deferred per explicit user instruction to stop
  # re-chasing CI-only flakes with more runs.
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
  Scenario: Edit a result and verify it in chart view
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait for the page to be loaded when ready
    And I follow "exam"
    And I wait for the page to be loaded
    # Tag-agnostic ".mdi-*", NOT "i.mdi-*": the Assessments tool is Vue now
    # (/resources/gradebook/<node>/) and its icons are rendered by BaseButton as
    # <span class="p-button-icon mdi mdi-...">, never <i>. Measured on the live
    # page: i.mdi-link-plus / i.mdi-pencil / i.mdi-chart-box all match ZERO
    # elements, while .mdi-link-plus matches 1 and .mdi-pencil matches 3, every
    # one of them a SPAN. The "i." prefix is a leftover from the legacy
    # displaygradebook.php era (which really did emit <i> via
    # Display::getMdiIcon), and because resolveField/click waits rather than
    # failing fast, it presented as a 90s timeout instead of "no such element".
    # Same fix already applied in sessionAccess.feature for the same reason.
    # REWRITTEN 2026-08-23: scores are edited INLINE now, not through a
    # pencil-icon dialog. Verified on the live results view
    # (/resources/gradebook/231/evaluations/<id>/results): the learner row is
    # "norizales | Orizales | Noa | <score input> / 10" and the page contains
    # ZERO .mdi-pencil elements — the only row action is Delete. So the old
    # sequence (click a pencil, fill a "score" field, press
    # "edit_result_form_submit") had three separate identifiers that cannot
    # match anything, and presented as a 90s timeout on the pencil.
    #
    # Note the icon fix applied elsewhere in this file was necessary but not
    # sufficient here: ".mdi-pencil" is the right SHAPE of selector, there is
    # simply no pencil on this particular view at all.
    #
    # Reuses "I fill in the score for ... with ..." + "Save", which is exactly
    # what this file's own "Create an evaluation" scenario already does for the
    # initial grade (line ~257) and which passes — so this now drives the score
    # the same proven way instead of inventing a second mechanism.
    When I fill in the score for "norizales" with "8"
    And I press "Save"
    And I wait for the page content to settle
    Then I follow "Assessments"
    And I wait for the page to be loaded
    # Tag-agnostic ".mdi-*", NOT "i.mdi-*": the Assessments tool is Vue now
    # (/resources/gradebook/<node>/) and its icons are rendered by BaseButton as
    # <span class="p-button-icon mdi mdi-...">, never <i>. Measured on the live
    # page: i.mdi-link-plus / i.mdi-pencil / i.mdi-chart-box all match ZERO
    # elements, while .mdi-link-plus matches 1 and .mdi-pencil matches 3, every
    # one of them a SPAN. The "i." prefix is a leftover from the legacy
    # displaygradebook.php era (which really did emit <i> via
    # Display::getMdiIcon), and because resolveField/click waits rather than
    # failing fast, it presented as a 90s timeout instead of "no such element".
    # Same fix already applied in sessionAccess.feature for the same reason.
     # "[title='List view']", NOT an mdi icon class. My first pass here changed
    # "i.mdi-chart-box" to ".mdi-chart-box" assuming the <i>-vs-<span> trap that
    # genuinely applied to the other icons in this file. That was WRONG: on this
    # page .mdi-chart-box is the GLOBAL SIDEBAR "Reporting" nav link, not a
    # gradebook control at all, so clicking it never reached any report (the URL
    # did not even change) and the assertion below then failed on a page that
    # was never the intended one.
    #
    # The gradebook's own report controls are two title-bearing links, dumped
    # live: [title='List view'] -> /resources/gradebook/<node>/reports/list and
    # [title='Students list report'] -> .../reports/students. Only the LIST view
    # renders the per-learner score in the "8 / 10" form this scenario asserts
    # (verified: its row reads "Noa Orizales norizales 8 / 10 - 72 % (72 / 100)",
    # whereas the students report shows the learner without that score cell).
    And I click the "[title='List view']" element
    And wait for the page to be loaded when ready
    Then I should see "8 / 10"

  Scenario: Open certificate from list view in Assessments
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I should see "Minimum certification score"
    Then I press "Certificate"
    And I wait for the page content to settle
    Then I press "Generate"
    And I wait for the page content to settle
    Then I should see "Noa Orizales"
    And I should see "Certificate"

  Scenario: Admin exports all to PDF
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I should see "Minimum certification score"
    Then I press "Students list report"
    And I wait for the page content to settle
    And I press "Export to PDF"
    Then I should not see an error

  # @skip 2026-08-06: recurring real-CI-only failure across multiple runs.
  # A prior investigation found no deterministic bug (one clean local run
  # passed all 10/10 scenarios; other local runs showed signs of shared-box
  # contamination from concurrent activity, not a real defect in this
  # scenario's own logic). Deferred per explicit user instruction to stop
  # re-chasing CI-only flakes with more runs. Leaves the "exam" evaluation
  # undeleted when skipped — harmless to other files (course TEMP's own
  # Assessments tool isn't asserted-empty anywhere else in this suite).
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
  Scenario: Deletes selected assessments
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And wait for the page to be loaded when ready
    Then I follow "Select all"
    # The bulk-action UI is a PrimeVue Select + Apply + ConfirmDialog, not a
    # button called "Action". Reconstructed from the live page, step by step:
    #   - "Action" is a <label> inside span.p-floatlabel, i.e. the FLOATING
    #     LABEL of a PrimeVue Select — there is no button with that name, so
    #     `I press "Action"` could never match anything. There is also no native
    #     <select> at all (PrimeVue 4 renders a div[role=combobox]), so the
    #     "I select ... from ..." steps do not apply either.
    #   - Scoping to span.p-floatlabel is what makes the combobox unambiguous:
    #     the page has TWO [role=combobox] elements and the other one is the
    #     paginator (its options are 10/20/50/100).
    #   - The option is "Delete", NOT "Delete selected" — the real option list
    #     is ["Select an action", "Set visible", "Set invisible", "Delete"].
    #   - Choosing the option does nothing on its own; an "Apply" button commits
    #     it, and that raises a ConfirmDialog reading "Confirmation / Delete
    #     all? / Cancel / Yes".
    # Verified end to end: after Yes the table shows "No data available", the
    # "Deleted" message appears and "exam" is gone.
    #
    # "Apply" goes through `I press` deliberately: getByRole("button",
    # { name: /^Apply$/i }) matches ZERO elements even though a visible button
    # whose text is exactly "Apply" exists — the same PrimeVue accessible-name
    # quirk already documented for pressButton, whose text-content tier does
    # match it.
    And I click the "span.p-floatlabel [role='combobox']" element
    And I click the "[role='option']:has-text('Delete')" element
    And I press "Apply"
    And I press "Yes"
    And wait for the page to be loaded when ready
    Then I should see "Deleted"
    And I should not see "exam"
    And I should not see "Assessment Link Work"
    And I should not see an error

  # @skip 2026-08-06: cascades from "Link an Assignment to the evaluation and
  # edit its min score" above (also @skip'd) never actually creating the
  # "Assessment Link Work" assignment this scenario asserts on and deletes.
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
  Scenario: Admin deletes the dedicated assignment created for the link scenario
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I should see "Assessment Link Work"
    And I click the "input.p-checkbox-input" icon in the row for "Assessment Link Work"
    And I press "Delete selected"
    And I press "Yes"
    And wait for the page to be loaded when ready
    Then I should not see "Assessment Link Work"
    And I should not see an error

  Scenario: Reset certification minimum score and unsubscribe the learner
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assessments"
    And I wait for the page content to settle
    Then I should see "Minimum certification score"
    Then I press "Edit"
    When I fill in the following:
      | gradebook-category-certificate-min-score | 75 |
    And I uncheck "Generate certificates"
    And I press "Save"
    And I wait for the page content to settle
    Then I should see "75"
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Users"
    And I wait for the page content to settle
    Then I should see "Orizales"
    And I click the "button[title='Unsubscribe']" icon in the row for "Orizales"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see an error
