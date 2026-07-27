# Docker variant of tests/behat/features/actionInstall.feature.
#
# The upstream feature relies on the wizard's defaults (localhost / root / no
# password) because CI runs MySQL on the same host. In the stack the server is
# the `db` service with its own credentials, so step 4 is filled in explicitly.
#
# setup.sh copies this into tests/behat/features/ to run it, then removes it —
# it must not stay there, or a full-suite `behat` run would reinstall the portal
# in the middle of the suite.
@administration
Feature: Install portal (Docker)

  Scenario: Installation process
    Given I am on "/main/install/index.php"
    And I wait for the page to be loaded when ready
    Then I should see "Step 1 - Installation Language"
    Then I press "Next"
    Then I should see "Step 2 - Requirements"
    Then I press "New installation"
    Then I wait for the page to be loaded
    Then I should see "Step 3 - License"
    Then I check "accept_licence"
    Then I press "license-next"
    Then I should see "Step 4 - Database settings"
    Then wait for the page to be loaded
    Then I fill in the following:
      | dbHostForm     | db      |
      | dbPortForm     | 3306    |
      | dbUsernameForm | chamilo |
      | dbPassForm     | chamilo |
      | dbNameForm     | chamilo |
    And I press "Check database connection"
    And wait for the page to be loaded
    And I press "step4"
    Then I should see "Step 5 - Configuration settings"
    Then I fill in the following:
      | passForm         | admin                 |
      | emailForm        | admin@example.com     |
      | mailerDsn        | smtp://mailpit:1025   |
      | mailerFromEmail  | noreply@example.com   |
      | mailerFromName   | Chamilo Docker        |
    Then I press "step5"
    Then I should see "Step 6 - Last check before install"
    When I wait for the page to be loaded
    And I press "button_step6"
    And I wait one minute for the page to be loaded
    Then I should see "Step 7"
    And I should see "Go to your newly created portal"
