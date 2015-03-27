@administration
Feature: Access to company reports as admin
  In order to analyse reports of time spent on the platform
  As an administrator
  I need to be able to access the company reports

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/index.php"
    Then I should see "Reports"

  Scenario: Access the company report
    Given I am a platform administrator
    And I am on "/main/mySpace/company_reports.php"
    Then I should not see "not authorized"

  Scenario: Access the resumed version of the company report
    Given I am a platform administrator
    And I am on "/main/admin/company_reports_resumed.php"
    Then I should not see "not authorized"
