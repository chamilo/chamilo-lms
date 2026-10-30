# Issue #2341: A ZIP containing HTML and image assets must become a selectable
# Gradebook certificate template without breaking ordinary document uploads.
# The course is isolated from the shared TEMP test course and removed at the end.
@common @tools
Feature: Gradebook certificate ZIP templates
  Teachers can import a zipped HTML certificate with its referenced images.

  Scenario: Create a dedicated course for ZIP certificate testing
    Given I am a platform administrator
    And I am on "/main/admin/course_add.php"
    And I wait for the page to be loaded
    And I fill in "title" with "GBZIP2341"
    And I select "Language skills" from the ajax select "update_course_course_categories"
    And I select "English" from "course_language"
    When I press "submit"
    And wait very long for the page to be loaded
    Then I resolve the numeric id of course "GBZIP2341"

  Scenario: Import a zipped certificate and set it as the default template
    Given I am a platform administrator
    And I am on course "GBZIP2341" homepage
    And I follow "Assessments"
    And I wait for the page content to settle
    When I click the "[title='Attach certificate']" element
    And I wait for the page content to settle
    And I press "Upload"
    And I wait for the page to be loaded
    Then I should see "Drop files here"
    When I attach the file "/tests/playwright/fixtures/gh2341-certificate.zip" to the upload dropzone
    And I press "Upload 1 file"
    And I wait for the page content to settle
    Then I should see "templates_index.html"
    When I click the "[title='Set as default certificate']" icon in the row for "templates_index.html"
    And I wait for the page content to settle
    Then I should see "templates_index.html"
    And I should not see an error

  Scenario: Delete the isolated ZIP certificate test course
    Given I am a platform administrator
    And I am on course "GBZIP2341" homepage
    And I wait for the page content to settle
    And I press "More actions"
    And I follow "Course maintenance"
    And I wait for the page content to settle
    And I follow "Completely delete this course"
    And I wait for the page content to settle
    And I fill in the following:
      | delete-course-code | GBZIP2341 |
    And I check "delete-docs"
    And I click the "button.p-button-danger:has-text('Delete course')" element
    And I wait for the page content to settle
    Then I should not see "GBZIP2341"
