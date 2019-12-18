@administration
Feature: Install portal

  Scenario: See the company reports link on the admin page
    Given I am on "/main/install"
    Then I should see "Step1 – Installation Language"
    Then I press "Next"
    Then I should see "Step2 – Requirements"
    Then I press "New installation"
    Then I should see "Step3 – Licence"
    Then I check the "accept_licence" radio button
    Then I press "license-next"
    Then I should see "Step4 – Database settings"
    Then I fill in the following:
      | dbUsernameForm | root |
      | dbPassForm | |
      | dbNameForm | master |
    Then I press "step3"
    Then I should see "Database driver: pdo_mysql"
    Then I press "step4"
    Then I should see "Step5 – ConfigurationSettings"
    Then I fill in the following:
      | passForm | admin |
    Then I press "step5"
    Then I should see "Last check before install"
    Then wait very long for the page to be loaded
    Then wait very long for the page to be loaded
    Then wait very long for the page to be loaded
    Then I press "Install chamilo"
    Then wait very long for the page to be loaded
    Then wait very long for the page to be loaded
    Then wait very long for the page to be loaded
    Then I should see "Installation process execution"

