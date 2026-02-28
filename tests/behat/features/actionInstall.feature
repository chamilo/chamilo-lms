@administration
Feature: Install portal

  Scenario: Installation process
    Given I am on "/main/install/index.php"
    Then I should see "Step 1 - Installation Language"
    Then I press "Next"
    Then I should see "Step 2 - Requirements"
    Then I press "New installation"
    Then I wait for the page to be loaded
    Then I should see "Step 3 - License"
    Then I check "accept_licence"
    Then I press "license-next"
    Then I should see "Step 4 - Database settings"
    Then wait for the page to be loaded when ready
    Then I fill in the following:
      | dbUsernameForm | root |
      | dbPassForm | root |
      | dbNameForm | chamilo |
    Then I press "Check database connection"
    Then wait for the page to be loaded when ready
    Then I press "step4"
    Then I should see "Step 5 - Configuration settings"
    Then I fill in the following:
      | passForm | admin |
      | emailForm | admin@example.com |
      | mailerDsn | null://null  |
      | mailerFromEmail | noreply@example.com |
      | mailerFromName  | Chamilo Behat install |
    Then I press "step5"
    Then I should see "Step 6 - Last check before install"
    Then wait for the page to be loaded when ready
    Then I press "button_step6"
    Then wait one minute for the page to be loaded
    Then I should see "Step 7"
    Then I should see "Go to your newly created portal"

