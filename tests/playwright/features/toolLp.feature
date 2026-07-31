# NOT a straight port — the Learning Path tool has moved to a Vue SPA
# (assets/vue/views/lp/*.vue, routes under /resources/lp/...).
# public/main/lp/lp_controller.php's legacy actions (add_lp_category,
# add_lp, list) are now thin redirect shims into the Vue routes — confirmed
# live, not assumed from the old Behat file:
# - "Add a category"'s field has no id/name shaped like "name" any more —
#   it's `id="lp-category-title" name="title" aria-label="Title"` — filled
#   via the label "Title", and the submit button is a plain "Save".
# - "Create new learning path"'s field is `name="title"
#   aria-label="Learning path name"`, and the submit button reads
#   "Continue" (not "submit") — confirmed it still works through the
#   shared pressButton() step even though PrimeVue's icon+label layout
#   breaks getByRole's {exact:true} accessible-name match (same class of
#   issue already documented for "Save"/"Add" elsewhere): the exact-text
#   tier (button textContent, not accessible name) still matches it.
# - Creating a LP redirects straight into the LP builder
#   (/resources/lp/:tool/:lp/builder) — there is no separate "add content"
#   step; the builder IS the edit page, with "Create a new document"
#   already on it.
# - From the LP list, the row's pencil icon is `<a title="Edit
#   learnpath">` (not "Edit") going straight to the builder — "I follow
#   'Edit learnpath'" resolves it via the shared step's a[title=] tier.
#   Clicking the LP's own title text instead opens a temporary
#   STUDENT-VIEW PREVIEW (/resources/lp/.../runtime?isStudentView=true),
#   which is what the original "Enter LP" scenario actually meant.
# - The inline "Create a new document" dialog's title field is
#   `id="lp-inline-document-title" aria-label="Title"`; its content is a
#   TinyMCE instance — "I fill in the active tinymce editor with ..." is
#   used (same pattern as toolWork), since the id, while stable here, adds
#   no value over the active-editor approach already established.
#   Confirmed a REAL AMBIGUITY here: TinyMCE's own disabled toolbar "Save"
#   button and the dialog's real green "Save" button both expose an exact
#   accessible name of "Save" — already solved generically by pressButton's
#   aria-disabled filter (documented in common.steps.ts), not something
#   this file needs to work around.
# - "Delete a LP" (row's "More actions" menu) and "Delete a LP category"
#   (category's own "More actions" menu) both confirm via PrimeVue's
#   "Yes", not native confirm()/jqGrid's "Delete selected" flow the
#   original assumed. No "Deleted" toast appears after either — the
#   original's "Then I should see 'Deleted'" assertion is dropped, only
#   the item's absence is asserted (matches the pattern already confirmed
#   for toolGroup-adjacent LP investigation).
# - REAL DRIFT FOUND: a freshly-created LP is NOT visible to students by
#   default (confirmed: student-view list shows "You don't have any
#   learning path" even though the LP exists) — the original PDF-export-
#   icon scenarios never toggled visibility and would have passed
#   vacuously here (0 icons regardless of the setting, because the list is
#   empty for a student either way). Added an explicit "Show" step before
#   those scenarios so they exercise the real icon, confirmed live: toggling
#   admin/settings/lp's "Hide Learning Path PDF export" between "No"/"Yes"
#   correctly shows/hides the icon once the LP itself is visible.
# - The setting lives at "/admin/settings/lp" (category "lp", not
#   "course" as its SettingsManager category mapping might suggest), and
#   is now a `<select id="form_hide_scorm_pdf_link">` ("Yes"/"No"), not
#   the original's radio buttons — "I select ... from
#   'form_hide_scorm_pdf_link'" replaces "I check the ... radio button".
# - "Add an exercise to LP" was already dead/commented-out in the original
#   Behat file (references a pre-existing "Exercise 1" this suite never
#   creates) — dropped, not ported, matching this migration's convention.
Feature: LP tool
  In order to use the LP tool
  The teachers should be able to create LPs

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a LP category
    Given I am on "/main/lp/lp_controller.php?cid=1&action=add_lp_category"
    And I wait for the page to be loaded when ready
    When I fill in the following:
      | Title | LP category 1 |
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "LP category 1"

  Scenario: Create a LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=add_lp"
    And I wait for the page to be loaded when ready
    When I fill in the following:
      | Learning path name | LP 1 |
    And I press "Continue"
    And wait for the page to be loaded when ready
    Then I should see "LP 1"

  Scenario: Add document to LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded when ready
    And I follow "Edit learnpath"
    And wait for the page to be loaded when ready
    Then I press "Create a new document"
    And wait for the page to be loaded when ready
    When I fill in the following:
      | Title | Document 1 |
    And I fill in the active tinymce editor with "Sample HTML text"
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Document 1"

  Scenario: Enter LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    When I follow "LP 1"
    And wait for the page to be loaded when ready
    Then I should see "Document 1"

  Scenario: Make the LP visible to students
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded when ready
    When I press "Show"
    Then I should see an icon with title "Hide"

  Scenario: Check the PDF export in LP list if hide SCORM PDF link is false
    Given I am on "/admin/settings/lp"
    And I wait for the page to be loaded when ready
    And I select "No" from "form_hide_scorm_pdf_link"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on "/main/lp/lp_controller.php?cid=1&action=list&isStudentView=true"
    And I wait for the page to be loaded when ready
    Then I should see an icon with title "Export to PDF"

  Scenario: Check the PDF export in LP list if hide SCORM PDF link is true
    Given I am on "/admin/settings/lp"
    And I wait for the page to be loaded when ready
    And I select "Yes" from "form_hide_scorm_pdf_link"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on "/main/lp/lp_controller.php?cid=1&action=list&isStudentView=true"
    And I wait for the page to be loaded when ready
    Then I should not see an icon with title "Export to PDF"

  Scenario: LP exists and LP category exists
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded when ready
    Then I should see "Learning paths"
    Then I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    And I should see "LP category 1"

  Scenario: Delete a LP
    Given I am on "/main/lp/lp_controller.php?cid=1&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    And I click the ".lp-panel__action-buttons button[title='More actions']" element
    And I follow "Delete"
    And I press "Yes"
    And I wait for the page to be loaded when ready
    Then I should not see "LP 1"

  Scenario: Delete a LP category
    Given I am on "/main/lp/lp_controller.php?cid=1"
    And I wait for the page to be loaded when ready
    Then I should see "LP category 1"
    And I click the "i.mdi-dots-vertical" element
    And I follow "Delete"
    And I press "Yes"
    And I wait for the page to be loaded when ready
    Then I should not see "LP category 1"
