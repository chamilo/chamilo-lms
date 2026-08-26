# NOT a straight port — the Course Progress (thematic) tool has moved to a
# Vue SPA (assets/vue/views/courseProgress/*.vue, route
# /resources/course-progress/:node/...). public/main/course_progress/index.php
# is now a thin redirect shim mapping legacy ?action= params to the Vue
# routes — confirmed via source read AND a real run: every field/button
# below was verified live, not assumed from the old Behat file.
#
# - "title"/"course_progress_thematic_content" (create) and
#   "course_progress_plan_description_1" (plan) are still real, STABLE ids
#   (unlike toolWork's randomly-generated TinyMCE ids) — the existing
#   id-based "I fill in tinymce field ... with ..." step works unchanged.
# - "title[1]" is still genuine bracket-array naming, confirmed live.
# - "Thematic plan"/"Edit" are still `<a title="...">` links (not buttons);
#   "Delete" is a `<button aria-label="Delete">` — but its confirmation is
#   PrimeVue's ConfirmDialog ("Yes", via `useConfirmation`/`requireConfirmation()`),
#   NOT the original's assumed native confirm()/SweetAlert2 — "I press 'Yes'"
#   is used instead of "I confirm the popup".
# - While being EDITED, each objective/skill/methodology row's description
#   is a TinyMCE instance rendered inside a real <iframe> (confirmed live —
#   8 iframes on one plan page) — but Save redirects back to a plain
#   READ-ONLY view of the thematic section, where the same content renders
#   as ordinary HTML (a <paragraph>, confirmed via a real trace), not an
#   iframe. So the original assertion pattern (fill, save, assert the text
#   is visible) still works — it just isn't visible on the edit form itself
#   the instant after clicking Save; the redirect is what actually makes it
#   plain-text-assertable, not the intermediate iframe. A first pass here
#   mistakenly built a field-value-based assertion to route around the
#   iframe issue — unnecessary, since the standard "I should see" step
#   already works fine once you're looking at the right (post-redirect) page.
# - "Edit thematic section" (heading text) and "Update successful" (flash)
#   are both still literal, confirmed live.
Feature: Course progress tool

  Background:
    Given I am a platform administrator

  Scenario: Create a thematic section
    Given I am on "/main/course_progress/index.php?cid=3&action=thematic_add"
    And I wait for the page to be loaded
    Then I fill in the following:
      | title | Thematic 1 Test |
    And I fill in tinymce field "course_progress_thematic_content" with "Description for thematic"
    And I press "Save"
    And I wait for the page to be loaded when ready
    Then I should see "Thematic 1 Test"

  Scenario: Read and update the thematic plan
    Given I am on "/main/course_progress/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should see "Thematic 1 Test"
    When I click the "a[title='Thematic plan']" element
    And I wait for the page to be loaded when ready
    Then I should see "Thematic plan"
    And I fill in the following:
      | title[1] | Objective 1 Test |
    And I fill in tinymce field "course_progress_plan_description_1" with "Objective 1 description"
    And I press "Save"
    And I wait for the page to be loaded when ready
    Then I should see "Update successful"
    And I should see "Objective 1 Test"
    And I should see "Objective 1 description"

  Scenario: Update a thematic section
    Given I am on "/main/course_progress/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should see "Thematic 1 Test"
    When I click the "a[title='Edit']" element
    And I wait for the page to be loaded when ready
    Then I should see "Edit thematic section"
    And I fill in the following:
      | title | Thematic 1 Test Edited |
    And I fill in tinymce field "course_progress_thematic_content" with "Description edited"
    And I press "Save"
    And I wait for the page to be loaded when ready
    Then I should see "Thematic 1 Test Edited"

  Scenario: Delete a thematic section
    Given I am on "/main/course_progress/index.php?cid=3"
    And I wait for the page to be loaded
    Then I should see "Thematic 1 Test Edited"
    When I click the "button[aria-label='Delete']" element
    And I press "Yes"
    And I wait for the page to be loaded when ready
    Then I should not see "Thematic 1 Test Edited"
