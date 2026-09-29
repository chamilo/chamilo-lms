# Ported from tests/behat/features/toolNotebook.feature — rewritten, not
# verbatim. The Notebook tool has been migrated to Vue
# (NotebookListView.vue/NotebookFormView.vue, /resources/notebook/...) since
# the original scenario was written. Confirmed live against course "TEMP":
# - The "Add"/"Edit"/"Delete" selectors from the original scenario still
#   match exactly as written ("a[title='Add']", "[data-type='notebook']
#   a[title='Edit']", "[data-type='notebook'] button[title='Delete']") —
#   BaseButton renders an only-icon button with a `route` as a real <a
#   title="...">, and one with no route (Delete) as a real <button
#   title="...">, both inside NotebookListView.vue's BaseCard, which carries
#   data-type="notebook".
# - "I fill in editor field ... with ..." (the original step) only works on
#   legacy pages (window.setContentFromEditor, bundled in the legacy_app
#   webpack entry only) — the form is now Vue's BaseTinyEditor, so this uses
#   "I fill in tinymce field ... with ..." instead, targeting the real
#   editor id "notebook_content" (NotebookFormView.vue's editor-id prop).
# - The delete confirmation is PrimeVue's ConfirmDialog (useConfirmation.js),
#   not ".p-confirmdialog-accept" (never a real class here) — its accept
#   button is plain text "Yes", handled by the existing "I press" step, same
#   as class.feature's delete flow.
# - Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
#   comment for why.
# - Scopes the Edit/Delete clicks to THIS scenario's own note by its exact
#   title (new "... icon in the notebook entry for ..." step, see
#   common.steps.ts) rather than the original's blind first-match
#   "[data-type='notebook'] a[title='Edit']" — course "TEMP" is a shared
#   fixture course on the live test box, so a blind first-match click can
#   land on an unrelated pre-existing note instead of this scenario's own
#   (same trap documented in class.feature's header comment).
# - KNOWN APP BUG, confirmed live via request/response inspection: editing a
#   note (PATCH /api/notebook/{iid}) does not update it in place — it
#   silently creates a BRAND NEW note instead, leaving the original
#   untouched. Root cause: NotebookItemProcessor::process() (src/CoreBundle/
#   State/Notebook/NotebookItemProcessor.php) only loads the existing note
#   when `$operation instanceof Put`, but the operation actually registered
#   for this route (NotebookItem.php) is `Patch`, not `Put` — the instanceof
#   check is therefore always false, so every edit takes the "create new"
#   branch instead of updating. Out of scope to fix here (a test port, not a
#   bug fix); worked around below by also deleting the original, now-
#   orphaned note, so this scenario still leaves the DB exactly as it found
#   it regardless of if/when that bug gets fixed. The original Behat
#   scenario's own assertions (which never checked that the pre-edit title
#   disappeared) still pass unmodified — the "updated" text really does
#   appear and really does disappear after its own delete — the bug is
#   invisible to a scenario that doesn't also check for the first note.
@common @tools
Feature: Notebook tool
  In order to keep private notes in a course
  As a course member
  I want to manage only my own notebook entries

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Admin creates, edits and deletes a personal note
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Notebook"
    And I wait for the page content to settle
    And I follow "Add"
    And I wait for the page content to settle
    And I fill in "title" with "Playwright Notebook Note"
    And I fill in tinymce field "notebook_content" with "Initial note details"
    And I press "save"
    And I wait for the page content to settle
    Then I should see "Playwright Notebook Note"
    And I should see "Initial note details"

    When I click the "a[title='Edit']" icon in the notebook entry for "Playwright Notebook Note"
    And I wait for the page content to settle
    And I fill in "title" with "Playwright Notebook Note UPDATED"
    And I fill in tinymce field "notebook_content" with "Updated note details"
    And I press "save"
    And I wait for the page content to settle
    Then I should see "Playwright Notebook Note UPDATED"
    And I should see "Updated note details"

    When I click the "button[title='Delete']" icon in the notebook entry for "Playwright Notebook Note UPDATED"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Playwright Notebook Note UPDATED"

    # Cleanup for the known app bug documented above: the edit step created a
    # brand-new note rather than updating the original in place, leaving
    # "Playwright Notebook Note" (the pre-edit title) behind as an orphan.
    # Deleting it here keeps this scenario self-contained.
    When I click the "button[title='Delete']" icon in the notebook entry for "Playwright Notebook Note"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "Playwright Notebook Note"
