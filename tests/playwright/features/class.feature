Feature: Classes
  In order to use Chamilo
  As an administrator
  I need to be able to manage classes

  # NOT a straight port of tests/behat/features/class.feature: the legacy page
  # it targeted (public/main/admin/usergroups.php) is now just a redirect
  # stub to /admin/usergroups (a Vue SPA — assets/vue/views/admin/
  # UsergroupList.vue). Rewritten against the current UI instead: create/edit
  # happen in a dialog with no full page navigation (client-side AJAX, so no
  # "wait for the page to be loaded" between actions), and delete confirmation
  # goes through PrimeVue's ConfirmDialog (useConfirmation.js) rather than a
  # native confirm() or SweetAlert2 modal — its accept button is plain text
  # "Yes", handled by the existing "I press" step. Edit/Delete target their
  # own row explicitly (by title text) rather than "click the first match" —
  # this page always has other real classes alongside whatever this feature
  # creates (unlike career.feature's careers.php, which had none), and a
  # blind first-match click deleted one of them once already.

  Scenario: Create a class
    Given I am a platform administrator
    And I am on "/admin/usergroups"
    And wait for the page to be loaded
    When I press "Add a class"
    And I fill in the following:
      | title       | Class 1     |
      | description | Description |
    And I attach the file "/public/img/logo.png" to "picture"
    And I press "Add"
    Then I should see "Class 1"
    And I should not see an error

  Scenario: Edit a class
    Given I am a platform administrator
    And I am on "/admin/usergroups"
    And wait for the page to be loaded
    Then I should see "Class 1"
    And I click the "button[aria-label='Edit']" icon in the row for "Class 1"
    And I fill in the following:
      | title | Class 1 Edited |
    And I press "Save"
    Then I should see "Class 1 Edited"
    And I should not see an error

  Scenario: Delete a class
    Given I am a platform administrator
    And I am on "/admin/usergroups"
    And wait for the page to be loaded
    Then I should see "Class 1 Edited"
    When I click the "button[aria-label='Delete']" icon in the row for "Class 1 Edited"
    And I press "Yes"
    Then I should not see "Class 1 Edited"
