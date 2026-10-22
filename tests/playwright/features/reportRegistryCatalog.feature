# Non-destructive browser coverage for the stable report IDs added under #4358.
Feature: Report identifiers in the administrative reports catalog
  In order to find each migrated reporting page by a stable identifier
  As a platform administrator
  I need the reports catalog to show unique identifiers and canonical links

  Scenario: Modern reports display their identifiers in the catalog
    Given I am a platform administrator
    And I am on "/main/admin/reports_catalog.php"
    And I wait very long for the page to be loaded
    Then I should see "global_admin_trainers"
    And I should see "global_student_bosses"
    And I should see "global_works_in_session"
    And I should see "course_exams_report"
