@modern-web-guidance-8776 @mwg8776-mobile-sidebar
Feature: Accessible mobile sidebar navigation in Chamilo
  The mobile sidebar toggle must expose an accessible name and support keyboard activation.

  Scenario: A platform administrator can operate the mobile sidebar by keyboard
    Given I am a platform administrator
    And I am on "/account/home"
    When I inspect the MWG8776 mobile sidebar toggle
    Then the MWG8776 mobile sidebar toggle must support accessible keyboard interaction
