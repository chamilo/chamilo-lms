Feature: Subscribe users to the course

  Background:
    Given I am a platform administrator

  Scenario: Subscribe "amann" as student to the course "TEMP"
    Given I am on "/main/user/subscribe_user.php?keyword=amann&type=5&cidReq=TEMP"
    Then I should see "Aimee"
    Then I follow "Register"
    Then I should see "Aimee Mann has been registered to your course"

  Scenario: Unsubscribe user "amann" the course "TEMP"
    Given I am on "/main/user/user.php?cidReq=TEMP"
    Then I should see "Aimee"
    Then I follow "Unsubscribe"
    And I confirm the popup
    Then I should see "User is now unsubscribed"

  Scenario: Subscribe "acostea" as student to the course "TEMP" (leave it subscribed for further tests)
    Given I am on "/main/user/subscribe_user.php?keyword=acostea&type=5&cidReq=TEMP"
    Then I should see "Andrea"
    Then I follow "Register"
    Then I should see "Andrea Costea has been registered to your course"