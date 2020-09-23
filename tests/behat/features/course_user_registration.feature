Feature: Subscribe users to the course

  Background:
    Given I am a platform administrator

  Scenario: Subscribe "amann" as student to the course "TEMP"
    Given I am on "/main/user/subscribe_user.php?keyword=amann&type=5&cid=1"
    Then I should see "Aimee"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should see "User Aimee Mann (amann) has been registered to course TEMP"

  Scenario: Unsubscribe user "amann" the course "TEMP"
    Given I am on "/main/user/user.php?cid=1"
    Then I should see "Aimee"
    Then I follow "Unsubscribe"
    And I press "Yes"
    And wait very long for the page to be loaded
    Then I should see "User is now unsubscribed"

  Scenario: Subscribe "acostea" as student to the course "TEMP" (leave it subscribed for further tests)
    Given I am on "/main/user/subscribe_user.php?keyword=acostea&type=5&cid=1"
    Then I should see "Andrea"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should see "User Andrea Costea (acostea) has been registered to course TEMP"

  Scenario: Subscribe "fapple" as student to the course "TEMP" (leave it subscribed for further tests)
    Given I am on "/main/user/subscribe_user.php?keyword=fapple&type=5&cid=1"
    Then I should see "Fiona"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should see "User Fiona Apple Maggart (fapple) has been registered to course TEMP"

  Scenario: Subscribe "amann" again as student to the course "TEMP" (leave it subscribed for further tests)
    Given I am on "/main/user/subscribe_user.php?keyword=amann&type=5&cid=1"
    Then I should see "Aimee"
    Then I follow "Register"
    And wait very long for the page to be loaded
    Then I should see "User Aimee Mann (amann) has been registered to course TEMP"
