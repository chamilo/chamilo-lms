# Ported from tests/behat/features/accessCompanyReports.feature.
#
# This file and companyReports.feature are near-duplicate Behat sources —
# same Feature title ("Access to portal reports as admin"), same 5 URLs
# checked, same admin login. They are ported as two separate files anyway,
# matching this migration's 1:1 file-per-Behat-file convention (both are
# real, independently runnable `vendor/bin/behat` targets), rather than
# merged into one. The only real content difference is the 4th scenario
# below: this file asserts /main/admin/teacher_time_report.php shows the
# "Teachers time report" heading, while companyReports.feature only asserts
# it shows no error there — kept as-is (not reconciled) per Rule 3
# (surgical changes; port existing behavior, don't fix accidental drift
# between two source files).
#
# Every "I am on ..." step below is followed by "I wait for the page to be
# loaded", matching companyReports.feature's own steps for the same URLs
# (this file's Behat source omits it after the first scenario, but Playwright's
# "I am on" already awaits domcontentloaded via gotoReliably — the explicit
# wait step is added here anyway for consistency with the sibling file and
# with the rest of this migration's convention of an explicit wait before
# a text assertion).
Feature: Access to portal reports as admin
  In order to analyse reports of time spent on the platform
  As an administrator
  I need to be able to access the portal reports

  Scenario: See the company reports link on the admin page
    Given I am a platform administrator
    And I am on "/main/admin/index.php"
    And I wait for the page to be loaded
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

  Scenario: See the teacher time report
    Given I am a platform administrator
    And I am on "/main/admin/teacher_time_report.php"
    And I wait for the page to be loaded
    Then I should see "Teachers time report"

  Scenario: Access the teacher time report without authorization error
    Given I am a platform administrator
    And I am on "/main/admin/teacher_time_report.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"

  Scenario: See the teacher time by session report
    Given I am a platform administrator
    And I am on "/main/admin/teachers_time_by_session_report.php"
    And I wait for the page to be loaded
    Then I should see "Teachers time report by session"

  Scenario: Access the teacher time by session report without authorization error
    Given I am a platform administrator
    And I am on "/main/admin/teachers_time_by_session_report.php"
    And I wait for the page to be loaded
    Then I should not see "not authorized"
