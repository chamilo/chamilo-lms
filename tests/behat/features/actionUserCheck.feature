Feature: User check after installation

  Scenario: Check admin information
    Given I am a platform administrator
    And I am on "/main/admin/user_list.php?keyword=admin"
    # admin@example.com was set during installation in actionInstall.feature
    And wait for the page to be loaded
    Then I should see "admin"
    Then I follow "John"
    And wait for the page to be loaded
    Then I should see "John Doe"

  Scenario: Check anon information
    Given I am a platform administrator
    And I am on "/main/admin/user_list.php?keyword=anon"
    Then wait very long for the page to be loaded
    Then I should see "anon"
    Then I should see "anon"

