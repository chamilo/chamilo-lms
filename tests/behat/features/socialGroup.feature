Feature: Social Group
    In order to use the Social Network
    As an administrator
    I need to be able to create a social group, invite users and post a message

  Scenario: Create a social group
      Given I am a platform administrator
      And I am on "/main/social/group_add.php"
      When I fill in the following:
          | name          | Behat Test Group                  |
          | description   | This is a group created by Behat  |
      And I press "submit"
      Then I should see "Group added"

#    Scenario: Invite a friend to group
#        Given I am a platform administrator
#        And I have a friend named "fbaggins" with id "11"
#        When I invite to a friend with id "11" to a social group with id "1"
#        Then I should see "Invitation sent"
#
#    Scenario: Accept an invitation to social group
#        Given I am logged as "fbaggins"
#        And I am on "/main/social/invitations.php"
#        When I follow "accept-invitation-1"
#        Then I should see "User is subscribed to this group"
#
#    Scenario: Deny an invitation to social group
#        Given I am a platform administrator
#        And I have a friend named "sgamgee" with id "13"
#        And I invite to a friend with id "13" to a social group with id "1"
#        When I am logged as "sgamgee"
#        And I am on "/main/social/invitations.php"
#        And I follow "deny-invitation-1"
#        Then I should see "Group invitation was denied"
#
#    Scenario: Delete user from group
#        Given I am a platform administrator
#        When I try delete a friend with id "12" from the social group with id "1"
#        Then I should see "The user has been deleted"
