@administration
Feature: Install portal

  Scenario: Installation process
    Given I am on "/main/install/index.php"
    Then I should see "Step 1 - Installation Language"
    Then I press "Next"
    Then I should see "Step 2 - Requirements"
    Then I press "New installation"
    Then I should see "Step 3 - Licence"
    Then I click the ".field-checkbox > label" element
    Then I press "license-next"
    Then I should see "Step 4 - Database settings"
    Then I fill in the following:
      | dbUsernameForm | root |
      | dbPassForm | root |
      | dbNameForm | master |
    Then I press "step3"
    Then I should see "Database driver"
    Then I should see "pdo_mysql"
    Then I press "step4"
    Then I should see "Step 5"
    Then I fill in the following:
      | passForm | admin |
      | emailForm | admin@example.com |
    Then I press "step5"
    Then I should see "Last check before install"
    Then wait the page to be loaded when ready
    Then I press "button_step6"
    Then wait the page to be loaded when ready
    Then wait the page to be loaded when ready
    Then wait the page to be loaded when ready
    Then I should see "Step 7"
    Then I should see "Go to your newly created portal"

