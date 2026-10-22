Feature: Certificate credential publication consent
  The certificate owner must understand that consent is an opt-in before any manual issuance.

  Scenario: My certificates explains the optional verification process
    Given I am a platform administrator
    And I am on "/my-certificates"
    And I wait for the page to be loaded
    Then I should see "Publication consent"
    And I should see "Sepolia testnet"
    And I should not see an error
