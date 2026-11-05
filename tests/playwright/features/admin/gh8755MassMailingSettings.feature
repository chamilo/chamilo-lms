@common @gh8755
Feature: Optional mass mailing transport settings
  Administrators can configure a separate transport without exposing it to learners.

  Scenario: Administrator can see the optional mass mailing DSN setting
    Given I am a platform administrator
    And I am on "/admin/settings/mail"
    And I wait for the page to be loaded
    Then I should see "Mass mailing DSN"

  Scenario: Student cannot access mail transport settings
    Given I am a student
    And I am on "/admin/settings/mail"
    And I wait for the page to be loaded
    Then I should not see "Mass mailing DSN"
