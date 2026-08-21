Feature: Administration statistics legacy parity
  In order to keep the platform statistics behavior while replacing the legacy page
  As a platform administrator
  I want the Vue and Symfony implementation to preserve the legacy report structure and access rules

  Background:
    Given I am a platform administrator
    And I wait for the page to be loaded

  Scenario: Open the statistics landing page without selecting a report
    Given I am on "/admin/statistics"
    And I wait for the page to be loaded
    Then I should see "Statistics"
    And I should see "Courses"
    And I should see "Users"
    And I should see "System"
    And I should see "Social"
    And I should see "Session"
    And I should not see "You are here"
    And I should not see an error

  Scenario Outline: Open a migrated statistics report
    Given I am on "<path>"
    And I wait for the page to be loaded
    Then I should see "<text>"
    And I should not see an error

    Examples:
      | path                                                        | text                           |
      | /admin/statistics?report=courses                            | Courses                        |
      | /admin/statistics?report=tools                              | Tools access                   |
      | /admin/statistics?report=tool_usage                         | Select tools                   |
      | /admin/statistics?report=courselastvisit                    | Latest access                  |
      | /admin/statistics?report=coursebylanguage                   | Number of courses by language  |
      | /admin/statistics?report=courses_usage                      | Courses usage                  |
      | /admin/statistics?report=users                              | Number of users                |
      | /admin/statistics?report=recentlogins                       | Last 15 days                   |
      | /admin/statistics?report=logins&type=month                  | All logins                     |
      | /admin/statistics?report=logins&type=day                    | Last logins                    |
      | /admin/statistics?report=logins&type=hour                   | Last logins                    |
      | /admin/statistics?report=pictures                           | Picture                        |
      | /admin/statistics?report=logins_by_date                     | Date range                     |
      | /admin/statistics?report=no_login_users                     | Not logged in for some time    |
      | /admin/statistics?report=zombies                            | Latest access                  |
      | /admin/statistics?report=users_active                       | Date range                     |
      | /admin/statistics?report=users_online                       | Users active in a test         |
      | /admin/statistics?report=new_user_registrations             | Date range                     |
      | /admin/statistics?report=subscription_by_day                | Date range                     |
      | /admin/statistics?report=duplicated_users                   | By name                        |
      | /admin/statistics?report=user_session                       | Date range                     |
      | /admin/statistics?report=quarterly_report                   | Number of users registered and connected |
      | /admin/statistics?report=messagereceived                    | Distribution                   |
      | /admin/statistics?report=messagesent                        | Distribution                   |
      | /admin/statistics?report=friends                            | Contacts count                 |
      | /admin/statistics?report=session_by_date                    | Date range                     |

  Scenario: Social contacts preserve the legacy distribution table
    Given I am on "/admin/statistics?report=friends"
    And I wait for the page to be loaded
    Then I should see "Contacts count"
    And I should see "Name"
    And I should see "Distribution"
    And I should see "Count"
    And I should not see an error

  Scenario: A legacy statistics bookmark reaches the migrated report
    Given I am on "/main/admin/statistics/index.php?report=friends"
    And I wait for the page to be loaded
    Then I should see "Contacts count"
    And I should see "Distribution"
    And I should not see an error

  Scenario: A student cannot access administration statistics
    Given I am a student
    And I am on "/admin/statistics?report=tool_usage"
    And I wait for the page to be loaded
    Then I should not see "Select tools"

  Scenario: Open the Zombies maintenance report
    Given I am on "/admin/statistics?report=zombies"
    And I wait for the page to be loaded
    Then I should see "Latest access"
    And I should see "Active only"
    And I should see "Activate"
    And I should see "Deactivate"
    And I should see "Delete"
    And I should not see an error

  Scenario: Open the Duplicate users maintenance report
    Given I am on "/admin/statistics?report=duplicated_users"
    And I wait for the page to be loaded
    Then I should see "By name"
    And I should see "By email"
    And I should see "By extra field"
    And I should see "How to use this report"
    And I should not see an error
