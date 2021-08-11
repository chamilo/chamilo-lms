#Not tested yet, needs to be verified before uncomenting
#Feature: Social Wall
#  A user should be able to send message on the social wall and the message
#should be on its friend wall.
#
#  Background: Given I am a platform administrator
#     And I have a friend named "fbaggins" with id "11" 
#
#  Scenario: Send a new post
#    Given I am a platform administrator
#    And I am on "main/social/home.php"
#    Then I should see "Social wall"
#    When I fill in the following:
#      | social_wall_new_msg_main | Behat test social wall post content |
#    And I press "Post"
#    And wait for the page to be loaded
#    Then I should see "Message Sent"
#    And I should see "Behat test social wall post content"
#
#  Scenario: View friend's post on my wall
#    Given I am a platform administrator
#    And I have a friend named "fbaggins" with id "11"
#    When I am logged as "fbaggins"
#    And I am on "main/social/home.php"
#    Then I should see "Behat test social wall post content"
#
#  Scenario: Comment friend's post on my wall
#    Given I am a platform administrator
#    And I have a friend named "fbaggins" with id "11"
#    When I am logged as "fbaggins"
#    And I am on "main/social/home.php"
#    Then I should see "Behat test social wall post content"
#    When I fill in the following:
#      | comment | Behat test comment on social wall post |
#    And I press "Post"
#    And wait for the page to be loaded
#    Then I should see "Behat test comment on social wall post depuis moins
#d'une minute"


