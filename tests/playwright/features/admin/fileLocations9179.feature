Feature: File locations in administration
  As a platform administrator
  I want to locate a stored file in its courses or personal folders
  So I can find the file through Chamilo's interface

  Scenario: An administrator can inspect file locations without modifying files
    Given I am a platform administrator
    And I am on "/admin/files_info"
    And I wait for the page to be loaded
    Then I should see the ".open-modal" element
    When I click the ".open-modal" element
    Then I should see the "#file-locations" element
    And I should see the "#file-locations li" element
    And I should see "File locations:"
    And I should not see an error

  Scenario: Students cannot access the administrator's file inventory
    Given I am a student
    And I am on "/admin/files_info"
    And I wait for the page to be loaded
    Then I should not see the ".open-modal" element
