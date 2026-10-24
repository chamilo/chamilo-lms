@issue8954 @floorplans
Feature: Branch and room floor plans
  The floor plan attached to a branch must be editable only by administrators,
  while room polygons are validated and preserved by the modern Room API.
  Scenarios with mutations must run ONLY on an isolated Playwright database.

  Scenario: An administrator can open the floor-plan management interface
    Given I am an administrator for issue 8954
    When I open the floor-plan editor of a branch in this portal
    Then I should not see an error

  Scenario: An administrator can list eligible floor-plan images
    Given I am an administrator for issue 8954
    Then the floor-plan API "/api/branches/{branchId}/floor-plan/options" returns HTTP 200

  Scenario: An administrator cannot upload without an image
    Given I am an administrator for issue 8954
    Then the floor-plan upload without a file returns HTTP 400

  Scenario: A teacher cannot read the administrative floor-plan endpoints
    Given I am a teacher
    Then the floor-plan API "/api/branches/{branchId}/floor-plan" returns HTTP 403
    And the floor-plan API "/api/branches/{branchId}/floor-plan/options" returns HTTP 403
    And the floor-plan upload without a file returns HTTP 403

  Scenario: A student cannot read the administrative floor-plan endpoints
    Given I am a student
    Then the floor-plan API "/api/branches/{branchId}/floor-plan" returns HTTP 403
    And the floor-plan API "/api/branches/{branchId}/floor-plan/options" returns HTTP 403
    And the floor-plan upload without a file returns HTTP 403

  # Self-cleaning API coverage; never run on testing26 or production.
  Scenario: An administrator can create, read, update and delete a room polygon
    Given I am an administrator for issue 8954
    When I exercise temporary branch and room polygon CRUD
