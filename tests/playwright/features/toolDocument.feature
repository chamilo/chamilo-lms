# NOT a port — freshly written. The legacy public/main/document/document.php
# this feature originally targeted no longer exists at all: the Document
# tool has fully moved to a Vue SPA (assets/vue/views/documents/*.vue,
# route /resources/document/:node/), same "check for an already-migrated
# page before porting verbatim" situation class.feature hit first for
# usergroups.php. Every step below was verified against a real running
# instance first (not just source reading):
#
# - Reaching the tool: click the course's "Documents" tool link from the
#   course home — same as the original `I follow "Document"` intent. The
#   route's :node param (the course's document-root resourceNode id) is
#   resolved automatically by the tool link itself; nothing needs to compute
#   or hardcode it.
# - Folder create/rename and delete are dialog-based (DocumentsList.vue) —
#   no navigation happens, so no "wait for the page to be loaded" is
#   meaningful around them (matches how class.feature's UsergroupList.vue
#   dialogs were already handled). Toast is exactly "Saved" (not "saved" or
#   "created" as the old legacy-page assertions expected).
# - Text/HTML document create and edit DO navigate (to .../create and back
#   to the list with ?loadNode=1) — confirmed via a real run; no "created"
#   toast actually appears, so the assertion is the document's own title
#   text becoming visible in the list instead.
# - Upload uses Uppy's <Dashboard> component, not a custom dropzone — but
#   its default UI strings match the original assertions almost exactly
#   ("Drop files here", "Upload 1 file"), and it renders a real (visually
#   hidden) `<input type="file">` Playwright's setInputFiles() can target
#   directly, no special handling needed.
# - Row actions (Edit/Delete) are icon-only buttons carrying a `title`
#   attribute (not aria-label, not visible text) — reused the existing
#   "I click the ... icon in the row for ..." step (already scopes to the
#   right row by its title text) with a `[title='Edit']`/`[title='Delete']`
#   attribute selector instead of an mdi icon class.
# - Delete confirmation is a PrimeVue dialog with plain "Yes"/"No" buttons
#   (not this repo's useConfirmation composable's usual wording, still a
#   real "I press 'Yes'" per the existing pattern). "I press 'Yes'" itself
#   needed a pressButton() fix (see common.steps.ts) — this app's own
#   persistent sidebar-collapse toggle happens to render its current-state
#   label as literally "Yes" too (a separate, pre-existing app bug, out of
#   scope here), and a blind exact-text match could hit that instead of the
#   dialog's real button.
# - The multi-delete scenario chains 3 deletes back-to-back; each one MUST
#   be followed by a durable "I should not see <title>" assertion, not
#   "wait for the page to be loaded" (a no-op here per the dialog-based
#   point above, since nothing ever navigates) — without it, a real CI-like
#   run showed the very next row lookup racing the previous delete's still-
#   in-flight table refetch, silently timing out on a row that actually
#   would have appeared moments later. Waiting for the just-deleted title
#   to actually disappear before moving to the next one closes that race.
Feature: Document tool
  In order to use the document tool
  The teachers should be able to create and upload files

  Background:
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded

  Scenario: Create a folder
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I press "New folder"
    And I fill in the following:
      | title | My new directory |
    And I press "Save"
    Then I should see "Saved"

  Scenario: Create a text document
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I press "New document"
    And wait for the page to be loaded
    And I fill in the following:
      | title | My first document |
    And I fill in tinymce field "item_content" with "This is my first document!"
    And I press "Save"
    And wait for the page to be loaded
    Then I should see "My first document"

  Scenario: Create a HTML document
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I press "New document"
    And wait for the page to be loaded
    And I fill in the following:
      | title | My second document |
    And I fill in tinymce field "item_content" with "<a href='www.chamilo.org'>Click here</a><span><b>This is my second document!!</b></span>"
    And I press "Save"
    And wait for the page to be loaded
    Then I should see "My second document"

  Scenario: Upload a document
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I press "Upload"
    And wait for the page to be loaded
    Then I should see "Drop files here"
    Then I attach the file "/public/favicon.ico" to the upload dropzone
    Then I press "Upload 1 file"
    And wait for the page to be loaded
    Then I should see "favicon.ico"

  Scenario: Search for "My second document" and edit it
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My second document"
    Then I click the "[title='Edit']" icon in the row for "My second document"
    And wait for the page to be loaded
    Then I fill in the following:
      | title | My second document edited |
    Then I press "Save"
    And wait for the page to be loaded
    Then I should see "My second document edited"

  Scenario: Search for "My first document" and delete it
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I should see "My first document"
    Then I click the "[title='Delete']" icon in the row for "My first document"
    And I press "Yes"
    And wait for the page to be loaded
    Then I should not see "My first document"

  Scenario: Delete the remaining test documents
    Given I follow "Documents"
    And I wait for the page to be loaded
    Then I click the "[title='Delete']" icon in the row for "My second document edited"
    And I press "Yes"
    Then I should not see "My second document edited"
    Then I click the "[title='Delete']" icon in the row for "favicon.ico"
    And I press "Yes"
    Then I should not see "favicon.ico"
    Then I click the "[title='Delete']" icon in the row for "My new directory"
    And I press "Yes"
    Then I should not see "My new directory"
