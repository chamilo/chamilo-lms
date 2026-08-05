Feature: Special admin content creation to be able to run specific tests.
  As a platform administrator
  I want to run a few targeted scenarios that creates necessary content to run specific tests.

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  Scenario: Create 3 students accounts
    # Create three students to test internal messaging autocomplete
    And I am on "/main/admin/user_add.php"
    And I zoom out to maximum
    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname | Student |
      | lastname  | One     |
      | email     | student.one@example.test |
      | username  | studentone |
    And I select "Learner" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Set known password for studentone via user-list edit
    Given I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    When I fill in "Search users" with "studentone"
    And I click the "span.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "input[name='reset_password'][value='2']" to appear
    And I click the "input[name='reset_password'][value='2']" element
    And I wait for the element "[name='password']" to appear
    And I fill in "password" with "studentone"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I am on "/main/admin/user_add.php"
    And I zoom out to maximum
    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname | Student |
      | lastname  | Two     |
      | email     | student.two@example.test |
      | username  | studenttwo |
    And I select "Learner" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Set known password for studenttwo via user-list edit
    Given I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    When I fill in "Search users" with "studenttwo"
    And I click the "span.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "input[name='reset_password'][value='2']" to appear
    And I click the "input[name='reset_password'][value='2']" element
    And I wait for the element "[name='password']" to appear
    And I fill in "password" with "studenttwo"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Create a third student (no subscriptions) for default menu entry test
    And I am on "/main/admin/user_add.php"
    And I zoom out to maximum
    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname | Student |
      | lastname  | Three   |
      | email     | student.three@example.test |
      | username  | studentthree |
    And I select "Learner" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Set known password for studentthree via user-list edit
    Given I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    When I fill in "Search users" with "studentthree"
    And I click the "span.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "input[name='reset_password'][value='2']" to appear
    And I click the "input[name='reset_password'][value='2']" element
    And I wait for the element "[name='password']" to appear
    And I fill in "password" with "studentthree"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

