@administration
Feature: Users creation via CSV

  Scenario: Import user via CSV
    Given I am a platform administrator
    And I am on "/main/admin/user_import.php"
    And I wait for the page to be loaded
    Then I attach the file "/public/main/admin/example.csv" to "import_file"
    Then I press "Import"
    And wait very long for the page to be loaded
    Then I should not see an error
    Then I am on "main/admin/user_list.php?keyword=drbrown@example.net"
    And wait very long for the page to be loaded
    Then I should see "emmert"
