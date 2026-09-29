# Ported from tests/behat/features/registration.feature, verbatim —
# confirmed live that the "Sign up" homepage link, the registration form's
# field names (firstname/lastname/email/username/pass1/pass2), and the
# "Register" submit button are all unchanged.
Feature: User registration
  In order to enter the system
  I need to be able to create my account

  Scenario: Enter the registration form
    Given I am on the homepage
    And I wait for the page to be loaded
    Then I should see "Sign up"
    Then I follow "Sign up"
    Then I should see "Registration"
    And I wait for the page to be loaded
    And I fill in the following:
      | firstname     | user registration first name  |
      | lastname      | user registration last name   |
      | email         | user-registration@example.com |
      | username      | user_registration             |
      | pass1         | user-registration00!          |
      | pass2         | user-registration00!          |
    And I press "Register"
    And wait for the page to be loaded
    Then I should not see an error
