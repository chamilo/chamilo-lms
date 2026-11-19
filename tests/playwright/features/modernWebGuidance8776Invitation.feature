@modern-web-guidance-8776 @mwg8776-invitation-dialog
Feature: Invitation dialog accessibility in SocialSearch
  The invitation overlay must remain accessible without sending an invitation

  Scenario: An invite-eligible user's invitation dialog manages focus and keyboard dismissal
    Given I am a platform administrator
    And I am on "/social/search"
    When I open the MWG8776 invitation overlay for an invite-eligible user
    Then the MWG8776 invitation overlay must manage semantics and keyboard dismissal
