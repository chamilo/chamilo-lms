# Ported from Behat with several fixes for real drift found while porting
# (all confirmed by reading the current source, not guessed):
#
# - `/main/admin/user_list.php` is now a dead 2-line stub (the user list moved
#   to the Vue SPA at `/admin/user-list`, data via `/admin/user-list-data`) —
#   every scenario that used to hit it directly now targets `/admin/user-list`.
# - `user_add.php`'s old `status_select` status dropdown no longer exists —
#   replaced by a required multi-select `roles` field (rendered as
#   `<select name="roles[]">`, no `id`, and its `<label for="roles">` is
#   dangling since no element actually has `id="roles"` — so it must be
#   targeted by its real `name`, "roles[]", not by "Roles" or an id). Because
#   it's now required with no default, "Create a user with only basic info"
#   needed an explicit role added (it never selected one before, which used
#   to be fine because the old field defaulted to Student).
# - The role label is "Teacher", not "Trainer" (get_lang('Teacher') — the
#   original Behat table's "Trainer" was already stale before the
#   status_select removal). "Learner" was already correct and unchanged.
# - The "Delete a user"/"HRM follows ..." row actions are now PrimeVue
#   BaseButton icons (title-only, e.g. `title="Delete"`/`title="Assign users"`)
#   rather than legacy `<i class="mdi ...">` tags, and Delete's confirmation
#   is PrimeVue's ConfirmDialog (accept button text "Yes"), not a native
#   confirm() — same three-confirmation-mechanisms situation documented for
#   class.feature/courseCategory.feature.
# - The simple search input on `/admin/user-list` had no `name` attribute at
#   all (violates this project's own Behat-testability convention); added
#   `name="keyword"` directly to UserList.vue rather than working around it.
# - `dashboard_add_users_to_user.php`'s "NoAssignedUsersList[]" option labels
#   include the username suffix, e.g. "teacher firstname teacher lastname
#   (teacher)", not just the plain full name the original Behat table used.
# - "wait the page to be loaded when ready" (missing "for") is the same
#   pre-existing Behat typo already found/fixed in course.feature — no such
#   step was ever defined, so every scenario using it always errored out
#   there in the original suite. Fixed here too.
# - **Real CI-only failure found and fixed**: `user_add.php`'s manual
#   "password" field/group only renders when the platform setting
#   `security.admins_can_set_users_pass` is on — its schema default is `''`
#   (falsy), so a FRESH install never renders it at all, and the whole
#   password field is simply absent. The original Behat table's "password"
#   row happened to work on the long-lived shared dev box this migration
#   verifies against locally (that setting is enabled there from years of ad
#   hoc admin use), masking that it doesn't work on a truly fresh CI DB.
#   Removed the "password" row from every scenario that filled it EXCEPT
#   "Create a HRM user" — none of the others are actually testing manual
#   password-setting, so letting the account get its normal auto-generated
#   password is fine. "Create a HRM user" is different: "HRM logs as
#   teacher"/"HRM logs as student" need to actually log in as "hrm" with a
#   KNOWN password (same "username as its own password" convention every
#   other fixed test account here uses), which is only possible with a
#   manually-set password — so that scenario now enables
#   `admins_can_set_users_pass` via the settings UI first. UPDATE: this used
#   to be left unrestored deliberately ("low-risk permanent side effect") —
#   revisited because leaving ANY platform setting changed for the rest of
#   the run is exactly the class of bug that caused toolGroup.feature's
#   "0 categories rendered" mystery (a DIFFERENT setting, same root cause:
#   one file's mutation outliving its own run, observed by whatever else
#   happens to run concurrently or afterward). The @settings-createUser tag
#   below wires up a BeforeAll/AfterAll pair (registerSettingsGuard() in
#   common.steps.ts) that snapshots this setting's real current value before
#   this file's scenarios run and restores it after the last one finishes —
#   same mechanism adminSettings.feature's own @settings tag already used.
#   This also exposed a real robustness gap in `resolveField()`'s final
#   `getByLabel()` fallback (fixed in common.steps.ts): with the field gone,
#   it fell through to a case-insensitive SUBSTRING match on "password" and
#   silently latched onto an unrelated, permanently-hidden extra field
#   ("Moodle password"), hanging for the full test timeout instead of failing
#   fast — `resolveField()` now uses `{exact: true}` there.
# - **Real regression found and fixed** (not a test-only issue): the HR
#   manager "Login as" link in `my_space/teachers.php`/`student.php`
#   (`myStudents.php`) pointed at the same dead `admin/user_list.php` stub,
#   and even once pointed at the real working endpoint
#   (`/admin/user-list-login-as`), that controller's `#[IsGranted(...)]` only
#   allowed ROLE_ADMIN/ROLE_SESSION_MANAGER — `LoginAsAuthorizationChecker`
#   already has fully-correct DRH-impersonation logic (`canDrhLoginAs()`), it
#   was just unreachable for ROLE_HR users. Fixed both: `myStudents.php` now
#   builds the link the same way `my_space/course.php` already correctly
#   does, and `UserLoginAsController`'s IsGranted now also allows ROLE_HR.
#   The actual authorization decision is still fully enforced afterward by
#   `SwitchUserSubscriber`, which always delegates to
#   `LoginAsAuthorizationChecker` regardless of who reaches the controller.
# - **`user_add.php` itself has now been migrated to the Vue SPA** (`/admin/user-add`,
#   data/action via `/admin/user-add-data` / `/admin/user-add-action` — see
#   `UserAddController.php`, `UserAdd.vue`). Every scenario that used to hit the
#   legacy page directly now targets `/admin/user-add` instead, with these
#   selector changes:
#   - `roles[]` is a PrimeVue MultiSelect now (`input-id="roles"`, no real
#     `<select>` underneath) — targeted via the existing
#     `"I press the multiselect option ... in ..."` step instead of
#     `"I select ... from ..."`.
#   - The "Send mail to new user" radio group lost its legacy `#send_mail_no`
#     id (PrimeVue's `BaseRadioButtons` generates `${name}-${index}` ids
#     instead) — targeted by its visible label ("No") via the existing
#     `"I check the ... radio button"` step instead of a raw CSS selector.
#   - The submit button is a plain `BaseButton` labelled "Add" (no
#     `name="submit"` at all) — targeted by that visible text.
#   - The manual-password field now needs an explicit
#     `"I check the "Set password manually" radio button"` first (the legacy
#     page's password fieldset had no such toggle when
#     `admins_can_set_users_pass` was on — it just showed the field directly).
# - **Real bug found (this migration, not a test-only issue): "Create a user
#   with wrong email" never reached the backend at all.** `UserAdd.vue`'s
#   e-mail field had `type="email"` — the browser's OWN native format check
#   (triggered by any `type="email"` input holding a non-empty value,
#   regardless of `required`) silently blocked the form's native submit
#   event before Vue's `@submit.prevent` handler ever ran, so no
#   `/admin/user-add-action` request was ever sent (confirmed via the
#   trace's network log — no such request appears at all) and the server's
#   own "The email address is not complete..." message never had a chance
#   to be returned. Fixed by dropping `type="email"` (back to the default
#   `text`), matching the legacy page's own plain `<input type="text">` +
#   server-side-only email validation — same reasoning as why this project's
#   `check_password`/format checks generally live server-side, not as a
#   native HTML constraint that can pre-empt them.
#   - Validating "wrong username"/"wrong email" now happens server-side
#     (`UserAddController::create()`), not via client-side QuickForm rules —
#     confirmed the same underlying checks are still reused
#     (`UserManager::is_username_valid()`, `FILTER_VALIDATE_EMAIL`), so the
#     same error messages still appear, just after a real round-trip instead
#     of instant client-side validation.
# - **`user_edit.php` has now also been migrated to the Vue SPA**
#   (`/admin/user-edit/:userId`, data/action via `/admin/user-edit-data` /
#   `/admin/user-edit-action` — see `UserEditController.php`, `UserEdit.vue`).
#   Added three new scenarios ("Edit a user", "Edited user keeps the new
#   value", "Edit a user with wrong email") covering the "edit" leg of the
#   create/read/edit/delete matrix this file was otherwise missing, using the
#   "student" user created earlier in this same file. The list page's row
#   "Edit" icon (`title="Edit"`) now navigates client-side to the new route
#   instead of the dead `/main/admin/user_edit.php` stub, so the existing
#   `"I click the {string} icon in the row for {string}"` step reaches it
#   unchanged. The submit button is labelled "Save" (not "Add" — this is an
#   edit, not a create), matching this project's edit/update button
#   convention (orange/secondary, not green/success). The "phone" field lives
#   inside the collapsible "Advanced settings" panel (closed by default, same
#   as on the Add page) — the existing generic `"I press {string}"` step
#   clicks its real `<button>Advanced settings</button>` to expand it before
#   any field inside is reachable; confirmed live this genuinely fails
#   (`getByLabel(phone)` times out) without that step first.
#   **Real, generalizable step-infra gap found and root-caused (not guessed) while
#   stabilizing these 3 scenarios**: clicking the row's "Edit" icon triggers a
#   client-side Vue Router navigation (`router.push`/`<router-link>`), which never
#   fires a real browser navigation event — `page.waitForLoadState("domcontentloaded")`
#   (what "wait very long for the page to be loaded" actually does) resolves
#   immediately regardless of whether the SPA has actually swapped routes yet.
#   Confirmed directly: a throwaway script clicking the icon then immediately
#   checking `page.url()` after that wait still showed `/admin/user-list`, and a
#   `page.fill('input[name=email]', ...)` right after silently succeeded by filling
#   the LIST page's own "Advanced search" e-mail filter input (which also exists and
#   also matches `name="email"`) instead of throwing — so the scenario would appear
#   to "run" while operating on the wrong page entirely, then land on the real edit
#   page moments later with none of the intended actions applied. Fixed by replacing
#   that wait with `"I wait up to 10 seconds for the element ... to appear"` for a
#   string unique to the destination page (`"text=Edit user information"`, the edit
#   page's own heading) — waits for the actual route swap instead of a same page load
#   event that never fires. Any future scenario that clicks into a client-side
#   route change (not a full page load) should use this pattern, not the generic
#   "wait ... for the page to be loaded" family, which is a no-op for SPA-internal
#   navigation.
@administration @settings-createUser
Feature: Users management as admin
  In order to add users
  As an administrator
  I need to be able to create new users

  Background:
    Given I am a platform administrator

  Scenario: See the users list link on the admin page
    Given I am on "/main/admin/index.php"
    And wait very long for the page to be loaded
    Then I should see "Users list"
    And I should see "Add a user"

  Scenario: Create a user with only basic info
    And I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Sammy                 |
      | lastname  | Marshall              |
      | email     | smarshall@example.com |
      | username  | smarshall             |
    And I press the multiselect option "Learner" in "roles"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a user with wrong username
    And I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | NIÑO                  |
      | lastname  | NIÑO                  |
      | email     | example@example.com |
      | username  | NIÑO                  |
    And I press the multiselect option "Learner" in "roles"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should see "Only letters and numbers allowed"

  Scenario: Create a user with wrong email
    And I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Juls                  |
      | lastname  | Juls                  |
      | email     | NI -ÑO@example.com      |
      | username  | Juls                  |
    And I press the multiselect option "Learner" in "roles"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should see "The email address is not complete or contains some invalid characters"


  Scenario: Search a user
    Given I am on "/admin/user-list"
    And wait for the page to be loaded when ready
    And I fill in "keyword" with "smarshall"
    And I press "Search"
    And wait for the page to be loaded
    Then I should see "Sammy"
    And I should see "Marshall"


  Scenario: Delete a user
    Given I am on "/admin/user-list?keyword=smarshall"
    And wait very long for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "Marshall"
    And I press "Yes"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a HRM user
    # Unlike the other "Create a ... user" scenarios, this one's password
    # can't just be left auto-generated: "HRM logs as teacher"/"HRM logs as
    # student" below need to actually log in as "hrm" with a KNOWN password,
    # the same "username as its own password" convention every other fixed
    # test account in this suite uses (see loginAs() in common.steps.ts). That
    # requires the manual-password field, which only renders when
    # `security.admins_can_set_users_pass` is on — so enable it first.
    Given I am on "/admin/settings/search_settings?keyword=admins_can_set_users_pass"
    And wait for the page to be loaded when ready
    And I select "Yes" from "form_admins_can_set_users_pass"
    And I press "Save"
    And wait for the page to be loaded when ready
    Given I am on "/admin/user-add"
    And wait very long for the page to be loaded
    And I fill in the following:
      | firstname | HRM firstname|
      | lastname  | HRM lastname |
      | email     | hrm@example.com |
      | username  | hrm             |
    And I check the "Set password manually" radio button
    And I fill in "password" with "hrm"
    And I check the "No" radio button
    And I press the multiselect option "Human Resources Manager" in "roles"
    And wait very long for the page to be loaded
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a teacher user
    And I am on "/admin/user-add"
    And I wait for the page to be loaded
    And I fill in the following:
      | firstname | teacher firstname|
      | lastname  | teacher lastname |
      | email     | teacher@example.com |
      | username  | teacher  |
    And I press the multiselect option "Teacher" in "roles"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a student user
    Given I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | student firstname|
      | lastname  | student lastname |
      | email     | student@example.com |
      | username  | student   |
    And I press the multiselect option "Learner" in "roles"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Edit a user
    Given I am on "/admin/user-list"
    And wait for the page to be loaded when ready
    And I fill in "keyword" with "student"
    And I press "Search"
    And wait for the page to be loaded when ready
    And I wait up to 10 seconds for the element "tr:has-text('student lastname')" to appear
    And I click the "[title='Edit']" icon in the row for "student lastname"
    And I wait up to 10 seconds for the element "text=Edit user information" to appear
    And I press "Advanced settings"
    And I fill in "phone" with "0123456789"
    And I press "Save"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Edited user keeps the new value
    Given I am on "/admin/user-list"
    And wait for the page to be loaded when ready
    And I fill in "keyword" with "student"
    And I press "Search"
    And wait for the page to be loaded when ready
    And I wait up to 10 seconds for the element "tr:has-text('student lastname')" to appear
    And I click the "[title='Edit']" icon in the row for "student lastname"
    And I wait up to 10 seconds for the element "text=Edit user information" to appear
    And I press "Advanced settings"
    Then the field "phone" should have value "0123456789"

  Scenario: Edit a user with wrong email
    Given I am on "/admin/user-list"
    And wait for the page to be loaded when ready
    And I fill in "keyword" with "student"
    And I press "Search"
    And wait for the page to be loaded when ready
    And I wait up to 10 seconds for the element "tr:has-text('student lastname')" to appear
    And I click the "[title='Edit']" icon in the row for "student lastname"
    And I wait up to 10 seconds for the element "text=Edit user information" to appear
    And the field "email" should have value "student@example.com"
    And I fill in "email" with "NI -ÑO@example.com"
    And I press "Save"
    And wait very long for the page to be loaded
    Then I should see "The email address is not complete or contains some invalid characters"

  Scenario: HRM follows teacher
    Given I am on "/admin/user-list?keyword=hrm"
    And wait for the page to be loaded when ready
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I click the "[title='Assign users']" icon in the row for "HRM lastname"
    And wait for the page to be loaded when ready
    And I select "teacher firstname teacher lastname (teacher)" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM follows student
    Given I am on "/admin/user-list?keyword=hrm"
    And wait for the page to be loaded when ready
    And I should see "HRM lastname"
    And I should see "Human Resources Manager"
    And I click the "[title='Assign users']" icon in the row for "HRM lastname"
    And wait for the page to be loaded when ready
    And I select "student firstname student lastname (student)" from "NoAssignedUsersList[]"
    And I press "add_user_button"
    And I press "assign_user"
    And wait very long for the page to be loaded
    Then I should not see an error

  # @skip 2026-08-07: HR is no longer allowed to "log in as" a managed user in
  # this version. An earlier fix (this session) had widened
  # UserLoginAsController's #[IsGranted] gate to include ROLE_HR so this
  # scenario could pass — that was a deliberate product-policy decision to
  # revert, not a bug: the gate has been reverted back to ROLE_ADMIN/
  # ROLE_SESSION_MANAGER only (src/CoreBundle/Controller/Admin/
  # UserLoginAsController.php), so an HR user now gets AccessDeniedException
  # clicking this icon, same as before that fix ever existed.  Note: the
  # "login as" icon/link itself (my_space/teachers.php, student.php,
  # myStudents.php) is still visible to HR — only the controller-level gate
  # blocks the action once clicked. Revisit if the icon should be hidden
  # from HR entirely for a cleaner UX; not done here since only the
  # controller change was requested.
  # REWRITTEN 2026-08-22, not un-skipped as-is. The @skip note above is right
  # that HR "log in as" was deliberately reverted to being forbidden — so the
  # old assertion ("Then I should not see an error", i.e. the impersonation
  # SUCCEEDS) tests behaviour this version intentionally no longer has, and
  # could only ever be dead. Asserting the CURRENT policy instead turns a
  # permanently-dark test into real access-control coverage, which this
  # project's own testing rule explicitly asks for ("For access-restricted
  # pages, add a scenario verifying that non-privileged roles are denied").
  #
  # Deliberately still navigates all the way to the icon and clicks it: the
  # note above records that the icon REMAINS visible to HR by design and that
  # only the controller-level gate stops the action, so this keeps proving both
  # halves of that (reachable, but refused) rather than just asserting a URL is
  # forbidden.
  #
  # Verified live how the refusal actually manifests, rather than assuming a
  # 403 page: UserLoginAsController is gated by
  # #[IsGranted(new Expression('is_granted("ROLE_ADMIN") or
  # is_granted("ROLE_SESSION_MANAGER")'))], which excludes ROLE_HR, and
  # requesting /admin/user-list-login-as as an HR user answers
  # 302 -> http://<host>/ — the same redirect-to-index-with-a-flash behaviour
  # ExceptionListener applies to AccessDenied on plain browser navigations
  # (already documented in sessionAccess.feature). Hence the existing
  # "the URL should be the site root" step rather than a status-code or
  # error-text assertion, neither of which prod actually produces.
  Scenario: HRM cannot log in as a teacher
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/teachers.php"
    And wait for the page to be loaded when ready
    Then I should see "teacher lastname"
    Then I follow "teacher lastname"
    And wait for the page to be loaded
    And I click the "i.mdi-account-key" element
    And wait very long for the page to be loaded
    Then the URL should be the site root

  # Same policy reversal as "HRM cannot log in as a teacher" above, and
  # rewritten the same way — see that scenario's comment for the verified
  # redirect-to-site-root behaviour and why this asserts denial rather than
  # success.
  Scenario: HRM cannot log in as a student
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/student.php"
    And wait for the page to be loaded when ready
    Then I should see "student lastname"
    Then I follow "student lastname"
    And wait for the page to be loaded
    And I click the "i.mdi-account-key" element
    And wait very long for the page to be loaded
    Then the URL should be the site root
