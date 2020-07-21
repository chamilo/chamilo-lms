@administration
Feature: Users creation via CSV

  Scenario: Import user via CSV
    Given I am a platform administrator
    And I am on "/main/admin/user_import.php"
    Then I attach the file "/main/admin/example.csv" to "import_file"
    Then I press "Import"
    Then I should see "The user has been added"
    Then I am on "main/admin/user_list.php?keyword=drbrown@example.net"
    Then I should see "emmert"
