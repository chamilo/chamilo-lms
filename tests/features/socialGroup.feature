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

    Scenario: Invite a friend to group
        Given I am a platform administrator
        And I have a friend
        And I am on "/main/social/group_invitation.php?id=1"
        When I fill in "invitation[]" with "11"
        And I press "submit"
        Then I should see "Invitation sent"
