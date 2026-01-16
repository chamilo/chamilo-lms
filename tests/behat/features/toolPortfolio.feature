Feature: Portfolio tool
  In order to use the portfolio tool
  The teachers should be able to create portfolio items and comments


  Scenario: Create a portfolio item
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I wait for the page to be loaded
    And I zoom out to maximum
    Then I follow "Portfolio"
    And wait for the page to be loaded
    Then I follow "Add"
    And wait for the page to be loaded
    Then I fill in the following:
      | add_portfolio_title | PF1  |
    And I fill in editor field "content" with "Hello"
    And I press "add_portfolio_submit"
    And wait for the page to be loaded
    Then I should see "PF1"


 # Scenario: Add a new comment (example, commented as requested)
 #   Given I am a platform administrator
 #   And I am on course "TEMP" homepage
 #   And I wait for the page to be loaded
 #   And I zoom out to maximum
 #   Then I follow "Portfolio"
 #   And wait for the page to be loaded
 #   Then I follow "AddNewComment"
 #   And wait for the page to be loaded
 #   # Further steps to fill and submit the comment would go here
 #   # Then I should see "Your comment text"
