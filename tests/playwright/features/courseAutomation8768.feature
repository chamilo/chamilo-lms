# Manual course automation is configured below the reporting screen. The buttons
# must not execute destructive actions when no trigger has been configured.
# The full mutation matrix requires an isolated course and mail transport.
@common @tools
Feature: Course automation section in reporting
  As a course administrator
  I want to prepare course opening and closing actions from the reporting screen

  Scenario: Administration can inspect automation without triggering any changes
    Given I am a platform administrator
    And I wait for the page to be loaded
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow the course tool "Reporting"
    And I wait for the page to be loaded
    Then I should see "Automation"
    And I should see "Course opens now"
    And I should see "Course ends now"
    And I should see "Configured actions"
    And I should see the "button[name='automation-add-action']" element
