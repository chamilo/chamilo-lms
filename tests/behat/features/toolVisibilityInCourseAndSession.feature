Feature: Visibility of tools and appearance in a session context.
  The visibility in the course imply a reaction in the session context. 
  A tool set to visible in the course should be visible in the session context.
  A tool set to invisible in the course should not be present in the session context with default configuration.

  Background:
    Given I am a platform administrator
      
  @javascript
  Scenario: Create session visibility test
    And I am on "/main/session/session_add.php"
    When I fill in the following:
      | name | SessionVisibilityTest |
    And I fill in select2 input "#coach_username" with id "1" and value "admin"
    And I press "submit"
    Then I should see "Add courses to this session (SessionVisibilityTest)"
    Then I fill in ajax select2 input "#courses" with id "1" and value "TEMP"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "Update successful"
    And I should see "Subscribe users to this session"
    Then I fill in ajax select2 input "#users" with id "15" and value "fapple"
    And I press "submit"
    And wait very long for the page to be loaded
    Then I should see "SessionVisibilityTest"
    Then I should see "TEMP"
    Then I should see "fapple"

  Scenario: Document tool visible in course and in session
    Given I am on course "TEMP" homepage
#    Then I should see "mdi-eye" for the eye next to Documents
#    Then I am on /course/1/home?sid=1&gid=0
#    Then I should see "Documents"

  Scenario: Document tool invisible in course and in session
    Given I am on course "TEMP" homepage
#    And the eye next to document is "mdi-eye"
#    Then I click on the eye next to Documents
#    Then I should see "mdi-eye-off" for the eye next to Documents
#    Then I am on /course/1/home?sid=1&gid=0
#    Then I should not see "Documents"

# Those 2 cases can be repeated for every tool
