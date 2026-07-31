# NOT a straight port — the Work/Assignments tool has moved to a Vue SPA
# (assets/vue/views/assignment/*.vue, route /resources/assignment/:node)
# reached via the course-tool link. The legacy public/main/work/work.php
# still exists and still renders (confirmed via direct URL), but the actual
# "Assignments" course-tool link goes to the Vue page — same situation as
# toolAnnouncement's announcement tool. Every field/button/text below was
# verified against the live Vue page, not the legacy source:
# - "Create Assignment"/"Edit assignment" are icon-only buttons identified
#   only by their `title` attribute (no visible text) — resolved via the
#   existing pressButton()'s title tier.
# - The name field has no id/name, only `aria-label="Assignment name"` —
#   resolveField()'s final getByLabel(exact) tier handles it.
# - The rich-text field is a Vue BaseTinyEditor whose instance id is
#   randomly generated per mount (`tiny-vue_<random>`) — there's exactly one
#   editor open at a time in these dialogs, so the new "I fill in the active
#   tinymce editor with ..." step (uses `tinymce.activeEditor`, no id
#   needed) is used instead of the id-based tinymce step.
# - REAL RACE FOUND editing: the edit dialog's name field starts genuinely
#   EMPTY and only gets populated by an async fetch a moment after the
#   dialog opens — filling it too early gets silently overwritten once that
#   fetch resolves, so the save submits the OLD value (confirmed directly:
#   a save done immediately after opening the dialog showed a success
#   toast but the title was provably unchanged). Fixed by waiting for the
#   real value to actually appear before filling over it.
# - The assignment list's own row title is a plain `<a>` with NO `href`
#   attribute (confirmed via DOM dump) — no implicit link role, so a plain
#   "I follow" would never find it via the accessible-role tier; added a
#   plain-exact-text fallback tier to the shared "I follow" step for this
#   (harmless for every existing real `<a href>` usage, since that tier is
#   only reached when the role-based one already failed to match).
# - Kept flash-text assertions the original already got right: "Assignment
#   created" and "File uploaded successfully" are both still literal,
#   unchanged strings in the live app despite the full rewrite. "Assignment
#   Updated"/"Assignment name" field renamed to "name" in the original never
#   applied here (there never was a plain `name` field) — dropped, not
#   needed, since the edit scenario now asserts on the field's real
#   post-edit value instead.
# - The commented-out "Add a comment and attachment" scenario in the
#   original was already inert there — dropped, not ported, matching this
#   migration's convention for scenarios that were dead in the source suite.
Feature: Work tool
  In order to use the work tool
  The teachers should be able to create works

  Scenario: Create a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I press "Create Assignment"
    And wait for the page to be loaded
    When I fill in the following:
      | Assignment name | Work 1 Test |
    And I fill in the active tinymce editor with "Work description"
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Assignment created"

  Scenario: Edit a work
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "Work 1 Test"
    And wait for the page to be loaded when ready
    Then I should see "Work description"
    Then I press "Edit assignment"
    And wait for the page to be loaded when ready
    Then I fill in the following:
      | Assignment name | Work 1 Test Edited |
    And I press "Save"
    And wait for the page to be loaded when ready
    Then I should see "Assignment updated"
    Then I should see "Work 1 Test Edited"

  Scenario: Send work as student
    Given I am not logged
    Given I am a student
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    Then I should see "Work 1 Test Edited"
    Then I follow "Work 1 Test Edited"
    And wait for the page to be loaded when ready
    Then I should see "Work description"
    Then I press "Upload file"
    And wait for the page to be loaded
    Then I attach the file "/public/favicon.ico" to the upload dropzone
    And I press "Upload file"
    And wait for the page to be loaded when ready
    Then I should see "File uploaded successfully"

  Scenario: Check that work previously uploaded by student is available for the teacher
    Given I am not logged
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Assignments"
    And wait for the page to be loaded when ready
    And I follow "Work 1 Test Edited"
    And wait for the page to be loaded when ready
    Then I should see "Work description"
    And I should see "favicon"
