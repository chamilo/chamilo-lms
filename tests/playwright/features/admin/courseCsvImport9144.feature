Feature: Import courses from a CSV file
  The platform administrator should receive actionable validation errors
  instead of a silent page reload when a course CSV cannot be imported.

  Scenario: Reject an invalid course CSV without creating any courses
    Given I am a platform administrator
    And I am on "/main/admin/course_import.php"
    And I wait for the page to be loaded
    When I attach the file "tests/playwright/fixtures/admin/course_import_missing_category_9144.csv" to "import_file"
    And I press "Import"
    And I wait for the page to be loaded
    Then I should see "Missing required CSV column: CourseCategory."

  Scenario: A teacher cannot access the platform course importer
    Given I am a teacher
    And I am on "/main/admin/course_import.php"
    And I wait for the page to be loaded
    Then I should not see the "input[name='import_file']" element
