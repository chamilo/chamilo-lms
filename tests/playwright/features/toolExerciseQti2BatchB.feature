# Tests for supported QTI2 Batch B types in the Vue exercise importer.
# Run against Playwright TEMP fixtures only; do not run against production courses.
Feature: Exercise QTI2 Batch B import
  In order to reuse graphic hotspots and manually graded file questions
  As a course teacher
  I want QTI2 imports to preserve their type and grading configuration

  Scenario: Import hotspot, oral recording and file upload questions
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Import exercises Qti2"
    And I wait for the page content to settle
    And I attach the file "/tests/playwright/fixtures/qti2_batch_b_supported.zip" to the upload dropzone
    When I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I should see "Imported questions: 3"
    And I should not see "Skipped questions"
    When I follow "Edit questions"
    And I wait for the element "text=Select the blue and orange regions" to appear
    Then I should see "3 questions, for a total score (all questions) of 19."

  Scenario: Delete the imported QTI2 Batch B fixture
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QTI2 Batch B 6,13,23"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "QTI2 Batch B 6,13,23"
