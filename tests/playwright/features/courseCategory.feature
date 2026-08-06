Feature: Course category

  # course_category.php's list page has real pre-existing categories on this
  # shared dev box ("Language skills", "PC Skills", "Projects") — Edit/Delete
  # target their own row explicitly (by category name) rather than "click
  # the first match", same lesson as class.feature's usergroups.php (see
  # memory gotcha 13): a blind first-match click there deleted a real,
  # unrelated pre-existing row once already.
  #
  # Also: the original Behat table rows used "name" as the field key, but the
  # form's actual field is named "title" ($form->addElement('text', 'title',
  # get_lang('Category name'))) — confirmed directly against the live form
  # (no element resolves to "name" by id or name attribute at all). Another
  # pre-existing staleness in the original Behat file, like course.feature's
  # "wait the page to be loaded when ready" typo — this table row would have
  # thrown an ElementNotFoundException in the original suite too.

  Background:
    Given I am a platform administrator

  Scenario: Add a course category
    Given I am on "/main/admin/course_category.php?action=add"
    And I wait for the page to be loaded
    And I should see "Add category"
    When I fill in the following:
      | code | COURSE_CATEGORY |
      | title | Course category |
    Then I fill in editor field "description" with "description"
    Then I attach the file "/public/img/logo.png" to "picture"
    And I press "submit"
    And wait for the page to be loaded
    Then I should not see an error

  Scenario: Edit a course category
    Given I am on "/main/admin/course_category.php"
    And I wait for the page to be loaded
    Then I should see "Course category"
    And I click the "i.mdi-pencil" icon in the row for "Course category"
    And I wait for the page to be loaded
    Then I should see "Edit this category"
    Then I fill in the following:
      | title | Course category edited |
    Then I fill in editor field "description" with "description edited"
    And I press "submit"
    And wait for the page to be loaded
    Then I should not see an error
    And I should see "Course category edited"

  Scenario: Delete course category
    Given I am on "/main/admin/course_category.php"
    And I wait for the page to be loaded
    Then I should see "Course category edited"
    Then I click the "i.mdi-delete" icon in the row for "Course category edited"
    Then confirm the popup
    And wait for the page to be loaded
    Then I should not see "Course category edited"
