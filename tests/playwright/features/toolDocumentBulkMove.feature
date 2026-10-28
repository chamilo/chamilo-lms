Feature: Move selected course documents
  Teachers should be able to move several files in one action without breaking the folder tree.

  @slow-scenario
  Scenario: Move two documents to a folder and verify their destination
    Given I am a platform administrator
    And I am on course "TEMP" homepage
    And I follow "Documents"
    And I press "New folder"
    And I fill in the following:
      | title | Bulk 2536 destination |
    And I press "Save"
    Then I should see "Bulk 2536 destination"

    When I press "New document"
    And I fill in the following:
      | title | Bulk 2536 first |
    And I fill in tinymce field "item_content" with "First bulk move sample"
    And I press "Save"
    Then I should see "Bulk 2536 first"

    When I press "New document"
    And I fill in the following:
      | title | Bulk 2536 second |
    And I fill in tinymce field "item_content" with "Second bulk move sample"
    And I press "Save"
    Then I should see "Bulk 2536 second"

    When I select the document row "Bulk 2536 first"
    And I select the document row "Bulk 2536 second"
    And I press the document bulk move button
    And I choose the document destination "Bulk 2536 destination"
    And I press "Yes"
    Then I should see "Saved"
    And I should not see "Bulk 2536 first"
    And I should not see "Bulk 2536 second"

    When I open the document folder "Bulk 2536 destination"
    Then I should see "Bulk 2536 first"
    And I should see "Bulk 2536 second"
    When I return to the previous document folder
    Then I should see "Bulk 2536 destination"
    When I click the "[title='Delete']" icon in the row for "Bulk 2536 destination"
    And I press "Yes"
    Then I should not see "Bulk 2536 destination"
