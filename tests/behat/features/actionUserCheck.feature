Feature: User check after installation

  Scenario: Check admin information
    Given I am a platform administrator
    And I am on "/main/admin/user_list.php"
    # admin@example.com was set during installation in actionInstall.feature
    Then I should see "admin@example.com"
    Then I follow "John"
    Then I should see "John Doe"

  Scenario: Check anon information
    Given I am a platform administrator
    And I am on "/main/admin/user_list.php"
    Then I should see "anon"
    Then I should see "anon"

