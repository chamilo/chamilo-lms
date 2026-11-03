# Covers /reporting/sessions, the "Your sessions" list of the global reporting:
# the sessions the user follows (as coach, HR manager or session admin).
#
# Reads only: the list may be empty on the test portal, so the scenario checks
# the page frame (title, filter, table columns), not specific sessions.
Feature: Your sessions report
  In order to follow the sessions I am in charge of
  As a platform administrator
  I need a list of my sessions with their courses and learners

  Scenario: See the sessions list with its title, filter and columns
    Given I am a platform administrator
    When I am on "/reporting/sessions"
    And I wait up to 60 seconds for the element ".p-datatable" to appear
    Then I should see "Your sessions"
    And I should see "Number of courses per session"
    And I should see "Number of learners by session"
    And I should not see "Reset"
    And I should not see an error
