# New — issue #1377. The QTI2 importer (ExerciseQti2ImportService, behind the
# Vue "Import exercises Qti2" page) parsed matchInteraction items and then
# skipped them, so a Chamilo QTI2 export containing a Matching question came
# back without it ("Imported questions: 1" / "Skipped questions: 1" for the
# fixture below, confirmed live on master before the fix).
#
# Fixture: tests/playwright/fixtures/qti2_matching.zip holds the XML that
# ExerciseQti2ExportService::exportQuestion() produces for a Matching question
# (4 options, 3 items, scores 1/2/3, "Lisbon" left as a distractor) and a
# Unique answer question (score 1), wrapped like the exporter's own zip.
#
# The question list's inline preview (the "Preview" icon) renders the stored
# options as "A: Rome", "B: Paris"... and one table row per item with the
# option it matches and its score — enough to check every association.
#
# Fill in blanks (export added on the same branch): the fixture
# tests/playwright/fixtures/qti2_fill_blanks.zip holds what
# ExerciseQti2ExportService exports for a Fill in blanks question mixing a
# typed blank ([PHP], 1 point), a drop-down menu ([Linux|Windows|macOS],
# 2 points) and a typed blank with alternatives ([1.11||1.11.x], 3 points).
# It is imported, exported again with the list's "Export QTI2" icon and that
# download is imported a second time: both copies must keep the three blanks
# and the total score of 6.
#
# Cleanup: the imported exercises are deleted by the last scenario. No
# platform setting is touched.

Feature: Exercise QTI2 import
  In order to reuse tests exported from Chamilo or another QTI 2 tool
  As a teacher
  I need the QTI2 import to keep matching questions

  Background:
    Given I am a platform administrator

  Scenario: Import a QTI2 package containing a matching question
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Import exercises Qti2"
    And I wait for the page content to settle
    Then I should see "Import exercises QTI2"
    And I attach the file "/tests/playwright/fixtures/qti2_matching.zip" to the upload dropzone
    When I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I should see "Imported questions: 2"
    And I should not see "Skipped questions"
    When I follow "Edit questions"
    And I wait for the element "text=Match each country with its capital" to appear
    Then I should see "2 questions, for a total score (all questions) of 7."
    When I click the "[title='Preview']" icon in the row for "Match each country with its capital"
    Then I should see "D: Lisbon"
    And the row for "France" should contain "Paris"
    And the row for "Spain" should contain "Madrid"
    And the row for "Italy" should contain "Rome"

  Scenario: Import a QTI2 package containing a fill in blanks question
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Import exercises Qti2"
    And I wait for the page content to settle
    And I attach the file "/tests/playwright/fixtures/qti2_fill_blanks.zip" to the upload dropzone
    When I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I should see "Imported questions: 1"
    And I should not see "Skipped questions"
    When I follow "Edit questions"
    And I wait for the element "text=Complete the sentence about Chamilo" to appear
    Then I should see "1 questions, for a total score (all questions) of 6."
    When I click the "[title='Preview']" icon in the row for "Complete the sentence about Chamilo"
    Then the fill in blanks preview should show "PHP", "Linux|Windows|macOS" and "1.11||1.11.x"

  Scenario: Export the fill in blanks exercise to QTI2 and import it again
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I save the file linked by the "a[href*='/qti2-export']" icon in the row for "QTI2 fill blanks import" as "var/test-results/qti2_fill_blanks_export.zip"
    And I follow "Import exercises Qti2"
    And I wait for the page content to settle
    And I attach the file "var/test-results/qti2_fill_blanks_export.zip" to the upload dropzone
    When I press "Upload"
    And I wait for the page content to settle
    Then I should see "File imported"
    And I should see "Imported questions: 1"
    And I should not see "Skipped questions"
    When I follow "Edit questions"
    And I wait for the element "text=Complete the sentence about Chamilo" to appear
    Then I should see "1 questions, for a total score (all questions) of 6."
    When I click the "[title='Preview']" icon in the row for "Complete the sentence about Chamilo"
    Then the fill in blanks preview should show "PHP", "Linux|Windows|macOS" and "1.11||1.11.x"

  Scenario: Delete the imported QTI2 exercise
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QTI2 matching import"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "QTI2 matching import"

  Scenario: Delete the imported QTI2 fill in blanks exercises
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QTI2 fill blanks import"
    And I press "Yes"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "QTI2 fill blanks import"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "QTI2 fill blanks import"
