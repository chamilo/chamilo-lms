@administration
Feature: Access to portal reports as admin
  In order to analyse reports of time spent on the platform
  As an administrator
  I need to be able to access the portal reports

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/index.php"
    And wait for the page to be loaded
    Then I should see "Reports"

  Scenario: Access the company report
    Given I am a platform administrator
    And I am on "/main/my_space/company_reports.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"

  Scenario: Access the resumed version of the company report
    Given I am a platform administrator
    And I am on "/main/my_space/company_reports_resumed.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/teacher_time_report.php"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/teacher_time_report.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/teachers_time_by_session_report.php"
    And I wait for the page to be loaded
    Then I should see "Teachers time report by session"

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/teachers_time_by_session_report.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"
