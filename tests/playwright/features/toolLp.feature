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
# - REAL BUG FOUND (root-caused via a live CI failure, not guessed): "Enter
#   LP"'s "Then I should see 'Document 1'" was failing because "LP 1"'s
#   runtime view genuinely had zero items — NOT a rendering/timing race.
#   "Add document to LP"'s old "I follow 'Edit learnpath'" clicks the FIRST
#   a[title='Edit learnpath']:visible in DOM order (the shared step's
#   tiered fallback), which is only safe when "LP 1" is the only LP in
#   course TEMP's list. It never is on the shared dev box: confirmed via a
#   direct DB query that toolGlossary.feature's own "Create Learning path
#   named Glossary in course TEMP" scenario creates an LP titled "Glossary"
#   on every run with no teardown of its own, so several stray ones
#   accumulate over time and can sort before "LP 1". The document ended up
#   attached to one of those instead of "LP 1" (confirmed: c_lp_item's
#   "Document 1" row pointed at a "Glossary" LP's iid, not "LP 1"'s), so
#   "LP 1" itself had nothing but its root item. Fixed by scoping the click
#   to "LP 1"'s own row via the new "I click the ... icon in the LP panel
#   for ..." step (common.steps.ts), same shape as the card/notebook-entry/
#   table-row scoped steps already established there for this exact class
#   of bug. toolGlossary.feature's missing teardown of its own "Glossary" LP
#   is a separate, pre-existing defect in that file (fixed there, not here).
#   "Delete a LP" and "Delete a LP category" below had the exact same
#   unscoped-click vulnerability (confirmed live: with a stray extra "LP 1"
#   present, "Delete a LP" deleted the wrong one and its own "should not see
#   'LP 1'" assertion then failed against the survivor) — scoped the same
#   way via "... icon in the LP panel for ..." / "... icon in the LP
#   category header for ...".
# - The two "Check the PDF export..." scenarios below toggle
#   hide_scorm_pdf_link No then Yes with no teardown — they happen to leave
#   it at Yes, which IS this setting's actual schema default, but only
#   because that scenario runs last; not a deliberate guarantee, and the
#   same fragile pattern that left OTHER settings (show_session_description,
#   admins_can_set_users_pass) stuck at the WRONG value in other files. The
#   @settings-toolLp tag below wires up a BeforeAll/AfterAll pair
#   (registerSettingsGuard() in common.steps.ts) that snapshots this
#   setting's real current value before this file's scenarios run and
#   restores it after the last one finishes, regardless of scenario order.
@settings-toolLp
Feature: LP tool
  In order to use the LP tool
  The teachers should be able to create LPs

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage

  Scenario: Create a LP category
    Given I am on "/main/lp/lp_controller.php?cid=3&action=add_lp_category"
    And I wait for the page to be loaded when ready
    When I fill in the following:
      | Title | LP category 1 |
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "LP category 1"

  Scenario: Create a LP
    Given I am on "/main/lp/lp_controller.php?cid=3&action=add_lp"
    And I wait for the page to be loaded when ready
    When I fill in the following:
      | Learning path name | LP 1 |
    And I press "Continue"
    And wait for the page to be loaded when ready
    Then I should see "LP 1"

  Scenario: Add document to LP
    Given I am on "/main/lp/lp_controller.php?cid=3&action=list"
    And I wait for the page to be loaded when ready
    And I click the "a[title='Edit learnpath']" icon in the LP panel for "LP 1"
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
    Given I am on "/main/lp/lp_controller.php?cid=3&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    When I follow "LP 1"
    And wait for the page to be loaded when ready
    Then I should see "Document 1"

  Scenario: Make the LP visible to students
    Given I am on "/main/lp/lp_controller.php?cid=3&action=list"
    And I wait for the page to be loaded when ready
    When I press "Show"
    Then I should see an icon with title "Hide"

  # These two navigate to a LEGACY page under public/main with isStudentView in the URL.
  # Do not "clean that up": they are the regression guard for the guard added to
  # LegacyListener, which stops interpreting the parameter on API requests but must keep
  # honouring it on full page loads like this one. The SPA no longer sends it anywhere.
  # They leave the session in the student view, which is only safe because Playwright gives
  # each test a fresh browser context.
  Scenario: Check the PDF export in LP list if hide SCORM PDF link is false
    Given I am on "/admin/settings/lp"
    And I wait for the page to be loaded when ready
    And I select "No" from "form_hide_scorm_pdf_link"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on "/main/lp/lp_controller.php?cid=3&action=list&isStudentView=true"
    And I wait for the page to be loaded when ready
    Then I should see an icon with title "Export to PDF"

  Scenario: Check the PDF export in LP list if hide SCORM PDF link is true
    Given I am on "/admin/settings/lp"
    And I wait for the page to be loaded when ready
    And I select "Yes" from "form_hide_scorm_pdf_link"
    And I press "Save settings"
    And I wait for the page to be loaded when ready
    And I am on "/main/lp/lp_controller.php?cid=3&action=list&isStudentView=true"
    And I wait for the page to be loaded when ready
    Then I should not see an icon with title "Export to PDF"

  Scenario: LP exists and LP category exists
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded when ready
    Then I should see "Learning paths"
    Then I am on "/main/lp/lp_controller.php?cid=3&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    And I should see "LP category 1"

  Scenario: Delete a LP
    Given I am on "/main/lp/lp_controller.php?cid=3&action=list"
    And I wait for the page to be loaded when ready
    Then I should see "LP 1"
    And I click the ".lp-panel__action-buttons button[title='More actions']" icon in the LP panel for "LP 1"
    And I follow "Delete"
    And I press "Yes"
    And I wait for the page to be loaded when ready
    Then I should not see "LP 1"

  Scenario: Delete a LP category
    Given I am on "/main/lp/lp_controller.php?cid=3"
    And I wait for the page to be loaded when ready
    Then I should see "LP category 1"
    And I click the "i.mdi-dots-vertical" icon in the LP category header for "LP category 1"
    And I follow "Delete"
    And I press "Yes"
    And I wait for the page to be loaded when ready
    Then I should not see "LP category 1"
