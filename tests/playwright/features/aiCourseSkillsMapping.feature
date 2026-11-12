# Browser smoke coverage for #9119. The approval/write scenarios require a configured
# AI provider and isolated test database; see delivery/README_APPLY.md.
Feature: AI-assisted course skills mapping
  In order to review course competency suggestions
  As an authorized user
  I want a dedicated review page attached to the course analyzer

  Scenario: A platform administrator can open the skills mapping review page
    Given I am a platform administrator
    When I am on "/ai/course/3/skills"
    And wait for the page to be loaded
    Then I should see "AI-assisted skills mapping"

  Scenario: A student cannot access course skills mapping
    Given I am a student
    When I am on "/ai/course/3/skills"
    And wait for the page to be loaded
    Then I should not see "Suggest skills for this course"
