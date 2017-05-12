Feature: User registration
  In order to enter the system
  I need to be able to create my account

  Scenario: Enter the registration form
    Given I am on the homepage
    Then I should see "Sign up"
    Then I follow "Sign up!"
    Then I should see "Registration"
    And I fill in the following:
      | firstname     | user registration first name  |
      | lastname      | user registration last name   |
      | email         | user-registration@example.com |
      | official_code | user registration             |
      | username      | user_registration             |
      | pass1         | user-registration             |
      | pass2         | user-registration             |
    And I press "Register"
    And wait for the page to be loaded
    Then I should see "Your personal settings have been registered"
