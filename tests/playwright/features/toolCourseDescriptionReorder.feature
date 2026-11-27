@common @tools
Feature: Course description section order
  Scenario: Administrator creates, reorders, edits and removes sections
    Given I am a platform administrator
    And I create an isolated course for description ordering
    And I open the isolated course description
    When I add a custom description named "QA Description C"
    And I move description "QA Description C" up
    And I move description "QA Description C" up
    Then the isolated descriptions appear in order "QA Description C|QA Description A|QA Description B"
    When I reload the isolated course description
    Then the isolated descriptions appear in order "QA Description C|QA Description A|QA Description B"
    When I edit the description "QA Description A" to "QA Description A edited"
    Then the isolated descriptions appear in order "QA Description C|QA Description A edited|QA Description B"
    When I remove the description "QA Description B"
    Then the isolated descriptions appear in order "QA Description C|QA Description A edited"
    And I remove the isolated course for description ordering

  Scenario: Teacher reorders and learner can only read
    Given I am a platform administrator
    And I create an isolated course for description ordering
    And I enroll the teacher and learner in the isolated description course
    Given I am a teacher
    And I open the isolated course description
    When I move description "QA Description B" up
    Then the isolated descriptions appear in order "QA Description B|QA Description A"
    Given I am a student
    And I open the isolated course description
    Then the isolated descriptions appear in order "QA Description B|QA Description A"
    And I cannot reorder isolated course descriptions
    Given I am a platform administrator
    And I remove the isolated course for description ordering
