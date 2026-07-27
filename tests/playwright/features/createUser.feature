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
@administration
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
    And I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Sammy                 |
      | lastname  | Marshall              |
      | email     | smarshall@example.com |
      | username  | smarshall             |
      | password  | smarshall             |
    And I select "Learner" from "roles[]"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a user with wrong username
    And I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | NIÑO                  |
      | lastname  | NIÑO                  |
      | email     | example@example.com |
      | username  | NIÑO                  |
      | password  | smarshall             |
    And I check the "#send_mail_no" radio button selector
    And I click the "input#send_mail_no" element
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Only letters and numbers allowed"

  Scenario: Create a user with wrong email
    And I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Juls                  |
      | lastname  | Juls                  |
      | email     | NI -ÑO@example.com      |
      | username  | Juls                  |
      | password  | Juls                  |
    And I check the "#send_mail_no" radio button selector
    And I click the "input#send_mail_no" element
    And I press "submit"
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
    Given I am on "/main/admin/user_add.php"
    And wait very long for the page to be loaded
    And I fill in the following:
      | firstname | HRM firstname|
      | lastname  | HRM lastname |
      | email     | hrm@example.com |
      | username  | hrm             |
      | password  | hrm             |
    And I click the "input#send_mail_no" element
    And I select "Human Resources Manager" from "roles[]"
    And wait very long for the page to be loaded
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a teacher user
    And I am on "/main/admin/user_add.php"
    And I wait for the page to be loaded
    And I fill in the following:
      | firstname | teacher firstname|
      | lastname  | teacher lastname |
      | email     | teacher@example.com |
      | username  | teacher  |
      | password  | teacher00!   |
    And I select "Teacher" from "roles[]"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create a student user
    Given I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | student firstname|
      | lastname  | student lastname |
      | email     | student@example.com |
      | username  | student   |
      | password  | student00!   |
    And I select "Learner" from "roles[]"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should not see an error

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

  Scenario: HRM logs as teacher
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/teachers.php"
    And wait for the page to be loaded when ready
    Then I should see "teacher lastname"
    Then I follow "teacher lastname"
    And wait for the page to be loaded
    And I click the "i.mdi-account-key" element
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: HRM logs as student
    Given I am not logged
    Then I am logged as "hrm"
    And I am on "/main/my_space/student.php"
    And wait for the page to be loaded when ready
    Then I should see "student lastname"
    Then I follow "student lastname"
    And wait for the page to be loaded
    And I click the "i.mdi-account-key" element
    And wait very long for the page to be loaded
    Then I should not see an error
