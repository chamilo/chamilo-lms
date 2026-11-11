# Regression coverage for the shared legacy Skills form.
# Read-only checks: no Skill or Gradebook records are created or updated.
# Resolve an existing editable skill from the list instead of assuming fixed IDs.
Feature: Searchable gradebook categories in skill forms
  As a platform administrator
  I need to search gradebook categories without changing their stored IDs

  Scenario: Search gradebook categories in create and edit forms
    Given I am a platform administrator
    And I am on "main/skills/skill_create.php"
    And wait very long for the page to be loaded
    Then I should see the "#gradebook_id + .select2-container" element
    When I click the "#gradebook_id + .select2-container .select2-selection--multiple" element
    Then I should see the "#gradebook_id + .select2-container .select2-search__field" element
    When I type "Default" into the "#gradebook_id + .select2-container .select2-search__field" element
    Then I should see the ".select2-container--open .select2-results" element
    And I should not see an error

    # Open the first real skill from the list; skill id 1 is the structural root
    # and is not guaranteed to be a valid editable item in every installation.
    And I am on "main/skills/skill_list.php"
    And wait very long for the page to be loaded
    Then I should see the "a[href^='/main/skills/skill_edit.php?id=']" element
    When I click the "a[href^='/main/skills/skill_edit.php?id=']" element
    And wait very long for the page to be loaded
    Then I should see the "#gradebook_id + .select2-container" element
    When I click the "#gradebook_id + .select2-container .select2-selection--multiple" element
    Then I should see the "#gradebook_id + .select2-container .select2-search__field" element
    When I type "Default" into the "#gradebook_id + .select2-container .select2-search__field" element
    Then I should see the ".select2-container--open .select2-results" element
    And I should not see an error
