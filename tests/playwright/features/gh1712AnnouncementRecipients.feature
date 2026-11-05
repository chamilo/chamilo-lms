@common @announcement @gh1712
Feature: Announcement recipient controls
  The recipient form keeps groups separate from individual users and only exposes session delivery when available.

  Scenario: Recipient selectors reflect the course context
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    When I follow the course tool "Announcements"
    And I follow "Add an announcement"
    Then I wait for the element "#announcement_recipients" to appear
    And the GH1712 announcement controls should match the available recipients
