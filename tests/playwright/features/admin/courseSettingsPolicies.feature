@common
Feature: Platform policies for course settings
  In order to control which course settings teachers may change
  As a platform administrator
  I need a dedicated platform configuration page

  Scenario: Platform administrator can inspect the generated course settings policy list
    Given I am a platform administrator
    And I am on "/admin/settings/course-settings"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "student_validate_own_attendance"
    And I should see the "input[name='editable[student_validate_own_attendance]']" element

  Scenario: Teachers cannot access platform course-setting policies
    Given I am a teacher
    And I am on "/admin/settings/course-settings"
    And I wait for the page to be loaded
    Then I should not see "Default platform value"

  Scenario: Students cannot access platform course-setting policies
    Given I am a student
    And I am on "/admin/settings/course-settings"
    And I wait for the page to be loaded
    Then I should not see "Default platform value"

  Scenario: Shared enabled settings are identified by their tool category
    Given I am a platform administrator
    And I am on "/admin/settings/course-settings?q=wiki.enabled"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "wiki.enabled"
    And I should see the "input[name='editable[wiki.enabled]']" element

  Scenario: Attendance policies remain visible with legacy blank categories
    Given I am a platform administrator
    And I am on "/admin/settings/course-settings?q=student_validate_own_attendance"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "student_validate_own_attendance"
