@common @tools @slow-scenario @gh2872-lp-smoke
Feature: Learning path recycle bin smoke
  A course editor can open the archive panel without deleting learner data.

  Scenario: GH2872 administrator opens a temporary learning path recycle bin
    Given I am a platform administrator
    And I am on the learning path "add_lp" page of course "TEMP"
    When I create a temporary GH2872 learning path
    And I visit the GH2872 learning path reporting page
    And I wait up to 30 seconds for the element "button[title='Recycle bin']" to appear
    And I open the "learning path" recycle bin and verify its successful API response
    Then I should see "Learner score"
    And I should not see an error
    And I delete the temporary GH2872 learning path
