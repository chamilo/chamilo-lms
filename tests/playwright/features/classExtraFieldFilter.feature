Feature: Searchable user extra fields in class assignment
  In order to assign users efficiently
  As a platform administrator
  I need the class subscription screen to support searchable extra fields

  # Smoke coverage against the existing admin class-management UI. Actual matches
  # require a search-enabled user extra field and populated values; test those
  # with the environment-specific manual cases in README_APPLY.md.
  Scenario: Class subscription screen remains available with extra field filtering
    Given I am a platform administrator
    And I am on "/admin/usergroups"
    And wait for the page to be loaded
    When I press "Add a class"
    And I fill in the following:
      | title       | GH9068 filter smoke test |
      | description | Extra field filter test |
    And I attach the file "/public/img/logo.png" to "picture"
    And I press "Add"
    Then I should see "GH9068 filter smoke test"
    When I click the "button[aria-label='Subscribe users to class']" icon in the row for "GH9068 filter smoke test"
    Then I should see "Users on platform"
    And I should see "Users in group"
    And I should not see an error
    When I press "Back"
    And I click the "button[aria-label='Delete']" icon in the row for "GH9068 filter smoke test"
    And I press "Yes"
    Then I should not see "GH9068 filter smoke test"
