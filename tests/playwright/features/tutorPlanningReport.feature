# Covers /reporting/admin/tutor-planning, the Vue port of
# main/my_space/ti_report.php ("General tutor planning").
#
# As in the legacy page, the report is one table: a row per general tutor,
# the tutor's session count, then one column per ISO week ("YYYY-WW") of the
# date range. A week covered by a session is highlighted, and the session
# title (linking to the session) shows in its first week only.
#
# Self-contained: the session shown here is created by the first scenario,
# with jmontoya (seeded by yarn test:playwright:seed) as its general coach,
# and deleted by the last one. Without a date filter the week columns span
# the listed sessions, so the new session always has a column.
Feature: General tutor planning report
  In order to see when each general tutor is busy
  As a platform administrator
  I need a weekly planning table of the sessions of each general tutor

  Scenario: Create the session shown in this feature
    Given I am a platform administrator
    And I am on "/main/session/session_add.php"
    And I wait for the page to be loaded
    When I fill in the following:
      | title | Tutor planning session |
    And I select "jmontoya" from the ajax select "coach_username"
    And I press "submit"
    And I wait for the page to be loaded
    Then I should see "Add courses to this session"
    And I should not see an error

  Scenario: See the session in its tutor's weekly planning row
    Given I am a platform administrator
    And I am on "/reporting/admin/tutor-planning"
    And I wait up to 30 seconds for the element "table th:has-text('Tutor')" to appear
    Then I should see the "tr:has-text('Julio Montoya') td.bg-success a:has-text('Tutor planning session')" element
    And I should see the "table thead th:text-matches('^[0-9]{4}-[0-9]{2}$')" element

  Scenario: HR managers do not get access to the planning table
    Given I am an HR manager
    And I am on "/reporting/admin/tutor-planning"
    And I wait very long for the page to be loaded
    Then I should not see "Tutor planning session"

  Scenario: Learners do not get access to the planning table
    Given I am a student
    And I am on "/reporting/admin/tutor-planning"
    And I wait very long for the page to be loaded
    Then I should not see "Tutor planning session"

  Scenario: Delete the session shown in this feature
    Given I am a platform administrator
    And I am on "/admin/session-list?keyword=Tutor+planning+session"
    And I wait for the page to be loaded
    When I click the ".mdi-delete" icon in the row for "Tutor planning session"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "Tutor planning session"
    And I should not see an error
