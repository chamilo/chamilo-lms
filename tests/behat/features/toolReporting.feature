@common @tools
Feature: Reporting tool
  In order to check reporting pages
  As a course administrator
  I want to open each reporting link and ensure the pages load correctly


  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded


  Scenario: Admin navigates reporting pages and checks them
    Given I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    And I follow "Reporting"
    And I wait for the page to be loaded
    Then I should see "Andrea Costea"


   # Group reporting
    And I follow "Group reporting"
    And I wait for the page to be loaded
    Then I should not see an error


   # Report on resources
    And I follow "Report on resources"
    And I wait for the page to be loaded
    Then I should not see an error


   # Course report
    And I follow "Course report"
    And I wait for the page to be loaded
    Then I should not see an error


   # Exam tracking
    And I follow "Exam tracking"
    And I wait for the page to be loaded
    Then I should not see an error


   # Audit report
    And I follow "Audit report"
    And I wait for the page to be loaded
    Then I should not see an error


   # Learning paths generic stats
    And I follow "Learning paths generic stats"
    And I wait for the page to be loaded
    Then I should not see an error
