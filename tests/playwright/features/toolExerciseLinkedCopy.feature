@common @tools
Feature: Create linked exercises without duplicating questions
  A teacher can reuse a question in two exercises without creating a duplicate bank entry.
  The ordinary Copy action must remain an independent copy.

  @slow-scenario
  Scenario: Create and edit a linked exercise while preserving its source question
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I follow "Create exercise"
    And I wait for the page content to settle
    And I fill in "title" with "GH2076 source"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    And I follow the question type "Open question"
    And I wait for the page content to settle
    And I fill in "question" with "GH2076 shared question"
    And I fill in "exercise-manual-question-score" with "10"
    And I press "Save the question"
    Then I should see "GH2076 shared question"

    When I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Create linked exercise']" icon in the row for "GH2076 source"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should see "Linked exercise created"
    And I should see "GH2076 source - Linked"

    When I click the "[title='Configure']" icon in the row for "GH2076 source - Linked"
    And I wait for the page content to settle
    And I fill in "title" with "GH2076 linked updated"
    And I press "Proceed to questions"
    And I wait for the page content to settle
    Then I should see "GH2076 shared question"

    When I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    Then I should see "GH2076 source"
    And I should see "GH2076 linked updated"
    When I click the "[title='Edit questions']" icon in the row for "GH2076 linked updated"
    Then I should see "GH2076 shared question"
    When I click the "[title='Delete']" icon in the row for "GH2076 shared question"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "GH2076 shared question"

    When I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Edit questions']" icon in the row for "GH2076 source"
    Then I should see "GH2076 shared question"
    When I click the "[title='Delete']" icon in the row for "GH2076 shared question"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "GH2076 shared question"

    When I am on course "TEMP" homepage
    And I follow "Tests"
    And I wait for the page content to settle
    And I click the "[title='Delete']" icon in the row for "GH2076 linked updated"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "GH2076 linked updated"
    When I click the "[title='Delete']" icon in the row for "GH2076 source"
    And I press "Yes"
    And I wait for the page content to settle
    Then I should not see "GH2076 source"
