# features/courseTools.feature
@common
Feature: Course tools basic testing
  In order to use a course
  As a teacher
  I need to be able to enter a course and each of its tools

  Scenario: Make sure the course exists
    Given course TEMP exists
    Then I should not see an ".alert-danger" element

