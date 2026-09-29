# Ported from tests/behat/features/companyReports.feature.
#
# This file and accessCompanyReports.feature are near-duplicate Behat
# sources — same Feature title ("Access to portal reports as admin"), same
# 5 URLs checked, same admin login. They are ported as two separate files
# anyway, matching this migration's 1:1 file-per-Behat-file convention
# (both are real, independently runnable `vendor/bin/behat` targets),
# rather than merged into one. The only real content difference is the 4th
# scenario below: this file only asserts /main/admin/teacher_time_report.php
# shows no error, while accessCompanyReports.feature also asserts it shows
# the "Teachers time report" heading — kept as-is (not reconciled) per
# Rule 3 (surgical changes; port existing behavior, don't fix accidental
# drift between two source files). Confirmed live: that page does show the
# "Teachers time report" heading, so both assertions independently hold —
# this file's weaker check is just what its own Behat source asserted.
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

  Scenario: Access the teacher time report without error
    Given I am a platform administrator
    And I am on "/main/admin/teacher_time_report.php"
    And I wait for the page to be loaded
    Then I should not see an error

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
