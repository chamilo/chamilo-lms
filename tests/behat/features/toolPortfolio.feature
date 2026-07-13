Feature: Portfolio tool
  In order to document learning evidence
  As a course participant
  I should be able to use Portfolio without opening legacy pages

  Scenario: Create a portfolio item in the Vue interface
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Portfolio"
    And I wait for the page to be loaded
    And I follow "Add"
    And I wait for the page to be loaded
    And I fill in editor field "portfolio_title" with "Modern portfolio evidence"
    And I fill in editor field "portfolio_content" with "Evidence created from the Vue Portfolio form"
    And I press "save"
    And I wait for the page to be loaded
    Then I should see "Modern portfolio evidence"

  Scenario: Add a comment in the Vue interface
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    When I follow "Portfolio"
    And I wait for the page to be loaded
    And I follow "Modern portfolio evidence"
    And I wait for the page to be loaded
    And I press "Add a new comment"
    And I fill in editor field "portfolio_comment_content" with "Comment created from the Vue Portfolio dialog"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Comment created from the Vue Portfolio dialog"
