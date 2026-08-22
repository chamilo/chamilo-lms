# Ported from tests/behat/features/courseCatalogue.feature — rewritten, not
# verbatim. The course catalogue (`/catalogue/courses`) has moved to Vue
# (CatalogueCourses.vue) since the original scenario was written, confirmed
# live end-to-end:
#
# - The plain "search_catalogue" search box no longer exists at all — the
#   ONLY way to search by title now is the "Advanced search" toggle button
#   (BaseButton) revealing AdvancedCourseFilters.vue's "search_by_title"
#   field and an "Apply advanced filters" submit button. The original file's
#   4 search-by-title scenarios (plain search "test"/"course", then the same
#   two terms again "via filters") covered two UI paths that have since
#   collapsed into one — ported as 2 scenarios instead of 4 rather than
#   duplicate an identical flow twice.
# - "span.pi-sliders-h"/"span.pi-filter" (PrimeVue icon classes) don't match
#   anything on this page anymore — replaced by pressing the button's own
#   visible labels ("Advanced search" / "Apply advanced filters").
# - course_add.php's own success redirect now lands on "/admin/course-list"
#   (CourseList.vue's paginated table, sorted by title, 20/page) instead of a
#   page that trivially shows the new title — on a shared box with many
#   pre-existing courses, a freshly created "testcourse" is not guaranteed to
#   land on page 1 of an unfiltered list. Verified live: navigating to
#   "/admin/course-list?keyword=<title>" (CourseList.vue reads `keyword` off
#   the raw URL query string on mount) reliably finds it regardless of how
#   many other courses exist.
# - Same reasoning applies to "Update course extra field value": the
#   original blindly clicked the first pencil icon in an unfiltered course
#   list (only safe on a near-empty install) — scoped here to the exact
#   "grammartest" row via a keyword search first, matching the ONLY course
#   the final search-by-extra-field scenario expects to have Duration set.
# - The extra field's real update form field is "extra_duration" (not
#   "update_course_extra_duration" as the original Behat step used) —
#   confirmed via a live DOM dump of course_edit.php.
# - Neither course_add.php's create nor course_edit.php's extra-field update
#   shows a flash/toast on success (both are legacy header()+exit redirects,
#   same class of drift already documented elsewhere in this suite for
#   extra_fields.php) — "Then I should see 'testcourse'"/"Update successful"
#   are replaced by re-opening the relevant list/form and reading the real
#   state back (existing "the field ... should have value ..." step).
# - The catalogue's advanced-filters duration field renders with id
#   "extra-duration" (a hyphen, not the underscore the original scenario's
#   "extra_duration" assumed) — AdvancedCourseFilters.vue's default TEXT/
#   DURATION branch is `:id="`extra-${f.variable}`"`, confirmed live.
# - "Manage extra fields for courses", the "i.mdi-plus-box" add icon, every
#   course_field_* form field id, and "course_field_submit" are all
#   unchanged from the original (extra_fields.php is still legacy PHP).
# - Real CI failure investigated and reproduced live: the 3 search-by-filter
#   scenarios below each navigate to "/catalogue/courses" and then almost
#   immediately press "Advanced search"/fill/apply. A plain "wait for the
#   page to be loaded" (domcontentloaded only) is not enough there — it's a
#   real app-level race, not a test-timing nitpick. CatalogueCourses.vue's
#   onMounted() kicks off an initial unfiltered load() (page 1, itemsPerPage
#   12) and wires up an IntersectionObserver that can immediately trigger a
#   page-2 continuation of that SAME unfiltered load if the sentinel is
#   already in view. Neither load() call is cancelled or version-guarded:
#   applying a filter calls resetCatalogueState() (courses.value = [],
#   totalCourses.value = 0) and starts a NEW filtered load(), but if the
#   earlier unfiltered call (initial or page-2) is still in flight, its
#   response handler still runs afterwards and unconditionally does
#   `courses.value.push(...)` / sets totalCourses.value from ITS OWN
#   (stale, unfiltered) response — landing on top of the filtered result.
#   Reproduced directly (Playwright script, artificially delaying the first
#   /public_courses response so it resolves after the filtered one): the
#   catalogue showed a wrong "Matching courses" count, "grammarcourse"
#   visible despite filtering for "test", and "testcourse"/"grammartest"
#   each rendered TWICE — the exact symptom seen in the CI failure. This is
#   what actually produced the CI failure, not any duplicate course-creation
#   bug — "Create three courses for catalogue testing" was independently
#   re-verified live to create exactly one row per title, no duplicates.
#   CatalogueCourses.vue's load() now ignores stale responses (a generation
#   token bumped on every resetCatalogueState). The waits below still give
#   the filtered request time to paint before asserting titles.
#
# Cleanup: the original never deleted its 3 courses ("testcourse",
# "grammarcourse", "grammartest") or the "Duration" extra field, and this
# suite's own convention (see toolDocument.feature) is to leave the shared
# box as it was found — confirmed live that both a real delete UI exists
# ("Delete" row action on /admin/course-list, "Duration"'s own delete icon on
# extra_fields.php) and works, so the final scenario below uses them rather
# than documenting a limitation. The course_catalog_settings platform
# setting mutated by "Update catalogue settings..." is restored separately,
# by the @settings-courseCatalogue Before/AfterAll guard registered in
# common.steps.ts (same snapshot-before/restore-after pattern as this
# suite's other @settings-* tags), not by an explicit step here.
@settings-courseCatalogue
Feature: Course catalogue and extra fields
  In order to test course catalogue search and extra fields
  As an administrator
  I want to create courses, search them in the catalogue and manage extra fields

  Background:
    Given I am a platform administrator

  # @slow-scenario (4-minute budget, see the Before hook in common.steps.ts)
  # because this scenario legitimately does ~3x the work of a normal one and
  # measurably does not fit the config's default 90s: a real CI run timed out
  # at 91268ms — i.e. it overran by under 1.3 SECONDS. The run's own step
  # timings show nothing was broken, just slow: the Background login took
  # 8192ms, the catalogue-settings save ~13.6s, and each of the three
  # create-then-verify cycles ~20s (legacy course_add.php nav + fill + submit,
  # then a full Vue /admin/course-list SPA boot and keyword search). The first
  # two "I should see" assertions PASSED after 6095ms and 6672ms respectively;
  # the third, identical one was killed after only 3859ms of its own 15s
  # window, so it was aborted roughly 2-3s before it would have passed too.
  # Being only ~5-10% over budget is exactly the profile that flips between
  # pass and fail depending on CI load, which is why this scenario passed in
  # earlier runs and then started failing without any change to it.
  # @slow-scenario rather than @long-scenario: 4 minutes is ~2.6x the measured
  # need, whereas the 15-minute budget would let a genuine hang here burn a
  # quarter of an hour before reporting.
  @slow-scenario
  Scenario: Create three courses for catalogue testing
    Given I am on "/admin/settings/catalog"
    And I wait for the page to be loaded
    When I select the value "false" from "form_only_show_selected_courses"
    And I press "Save settings"
    And I wait for the page to be loaded
    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "testcourse"
    And I press "submit"
    And I wait for the page to be loaded
    Given I am on "/admin/course-list?keyword=testcourse"
    And I wait for the page to be loaded
    Then I should see "testcourse"

    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "grammarcourse"
    And I press "submit"
    And I wait for the page to be loaded
    Given I am on "/admin/course-list?keyword=grammarcourse"
    And I wait for the page to be loaded
    Then I should see "grammarcourse"

    Given I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    When I fill in "title" with "grammartest"
    And I press "submit"
    And I wait for the page to be loaded
    Given I am on "/admin/course-list?keyword=grammartest"
    And I wait for the page to be loaded
    Then I should see "grammartest"

  Scenario: Search courses in catalogue by title (search "test")
    Given I am on "/catalogue/courses"
    And I wait for the page content to settle
    And I press "Advanced search"
    When I fill in "search_by_title" with "test"
    And I press "Apply advanced filters"
    And I wait until I no longer see "Loading courses. Please wait."
    Then I should see "testcourse"
    And I should see "grammartest"
    And I should not see "grammarcourse"

  Scenario: Search courses in catalogue by title (search "course")
    Given I am on "/catalogue/courses"
    And I wait for the page content to settle
    And I press "Advanced search"
    When I fill in "search_by_title" with "course"
    And I press "Apply advanced filters"
    And I wait until I no longer see "Loading courses. Please wait."
    Then I should see "testcourse"
    And I should see "grammarcourse"
    And I should not see "grammartest"

  Scenario: Add an extra field "Duration" for courses
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Manage extra fields for courses"
    And I wait for the page to be loaded
    Then I click the "i.mdi-plus-box" element
    And I wait for the page to be loaded
    When I fill in "course_field_display_text" with "Duration"
    And I fill in "course_field_variable" with "duration"
    And I click the "input#visible_to_self_yes" element
    And I click the "input#visible_to_others_yes" element
    And I click the "input#changeable_no" element
    And I click the "input#filter_yes" element
    And I press "course_field_submit"
    And I wait for the page to be loaded
    Then I should see "duration"

  Scenario: Update course extra field value
    Given I am on "/admin/course-list?keyword=grammartest"
    And I wait for the page to be loaded
    Then I click the "[title='Edit']" icon in the row for "grammartest"
    And I wait for the page to be loaded
    And I save current URL with name "grammartest_edit"
    When I fill in "extra_duration" with "22:22:22"
    And I press "update_course_submit"
    And I wait for the page to be loaded
    Then I visit URL saved with name "grammartest_edit"
    And I wait for the page to be loaded
    Then the field "extra_duration" should have value "22:22:22"

  Scenario: Update catalogue settings to include extra field in search form
    Given I am on "/admin/settings/catalog"
    And I wait for the page to be loaded
    When I fill in "form_course_catalog_settings" with "{\"extra_fields_in_search_form\":[\"duration\"]}"
    And I press "Save settings"
    And I wait for the page to be loaded
    Given I am on "/admin/settings/catalog"
    And I wait for the page to be loaded
    Then the field "form_course_catalog_settings" should have value "{\"extra_fields_in_search_form\":[\"duration\"]}"

  Scenario: Search courses in catalogue by extra field (Duration = "22:22:22")
    Given I am on "/catalogue/courses"
    And I wait for the page content to settle
    And I press "Advanced search"
    When I fill in "extra-duration" with "22:22:22"
    And I press "Apply advanced filters"
    And I wait until I no longer see "Loading courses. Please wait."
    Then I should see "grammartest"
    And I should not see "testcourse"
    And I should not see "grammarcourse"

  # @skip 2026-08-05: consistently the last thing hit under real CI's concurrent
  # worker load — a plain page.goto("/admin/course-list?...") (legacy jqGrid admin
  # page) has hit the full 90s test timeout on its own navigation in multiple real
  # CI runs. Passes cleanly every time in isolation and even in a full local-suite
  # run right after a fresh install; only reproduces under CI's own concurrency
  # profile. Revisit once toolExerciseAdmin/toolGroup's own concurrent-load issues
  # (also @skip'd this session) are addressed — likely the same root class.
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
  Scenario: Clean up the test courses and the Duration extra field
    Given I am on "/admin/course-list?keyword=testcourse"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "testcourse"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "testcourse"

    Given I am on "/admin/course-list?keyword=grammarcourse"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "grammarcourse"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "grammarcourse"

    Given I am on "/admin/course-list?keyword=grammartest"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "grammartest"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "grammartest"

    Given I am on "/main/admin/extra_fields.php?type=course"
    And I wait for the page to be loaded
    Then I click the "a[href*='action=delete']" icon in the row for "Duration"
    And I wait for the page to be loaded
    Then I should not see "duration"
