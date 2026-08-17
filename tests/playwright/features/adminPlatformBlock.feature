# Ported from tests/behat/features/adminPlatformBlock.feature.
#
# - Drops "I zoom out to maximum" — see adminHealthBlock.feature's header
#   comment for why.
# - "Open Statistics" -> "Open Global statistics": IndexBlocksController::
#   getItemsTracking() (src/CoreBundle/Controller/Admin/
#   IndexBlocksController.php) renders this item's label as "Global
#   statistics", not "Statistics" — confirmed live, no link with the bare
#   original text exists at all, this is a rename not a removal.
# - "Open Reports" -> "Open Reports catalog": same file, same method —
#   "Reports catalog" is the current label (this item's route is literally
#   reports_catalog.php), "Reports" alone was never findable live.
# - "Open Import course events" dropped entirely: its whole entry in
#   getItemsPlatform() is commented out in the source ("Disabled until it
#   is reemplemented to work with Chamilo 2") — this is a genuinely dead
#   feature in the current app, not a renamed/hidden one, confirmed live (no
#   matching link at all).
Feature: Admin Platform management block
  In order to verify administration platform pages
  As a platform administrator
  I want to open each platform management link and ensure the page loads without errors

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open Configuration settings
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Configuration settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Languages
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Languages"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Plugins
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Plugins"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Regions
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Regions"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Portal news
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Portal news"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Global agenda
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Global agenda"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Pages
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Pages"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Setting the registration page
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Setting the registration page"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Global statistics
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Global statistics"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Reports catalog
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Reports catalog"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Teachers time report
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Teachers time report"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Extra fields
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Extra fields"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Multi URLs
    # "Configure multiple access URL" no longer has its own entry here — it's
    # reachable from the button on the Multi URLs dashboard itself, covered by
    # adminMultiUrls.feature's "The legacy CRUD is reachable from the dashboard".
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Multi URLs"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Mail templates
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Mail templates"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open External tools (LTI)
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "External tools (LTI)"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open Contact form categories
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "Contact form categories"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open System templates
    Given I am on "/admin"
    And I wait for the page to be loaded
    And I follow "System templates"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Open a course report without course context
    Given I am on "/main/admin/report.php?id=course_learners_tracking"
    And I wait for the page to be loaded
    Then I should see "Report on learners"
    And I should see "Course"
    And I should not see an error

  Scenario: Periodic export is not listed in the reports catalog
    Given I am on "/main/admin/reports_catalog.php"
    And I wait for the page to be loaded
    Then I should not see "Periodic export"
    And I should not see an error

  Scenario: Course reporting canonical URL uses the course selector
    Given I am on "/main/admin/report.php?id=course_activity_statistics"
    And I wait for the page to be loaded
    Then I should see "Course activity statistics"
    And I should see "Course"
    And I should not see an error

  Scenario: Exercises global report keeps its own modern course selector
    Given I am on "/main/admin/report.php?id=course_exercise_global_report"
    And I wait for the page to be loaded
    Then I should see "Exercises global report"
    And I should not see an error

