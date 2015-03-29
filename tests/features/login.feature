# features/login.feature
@common
Feature: User login
  In order to log in
  As any registered user
  I need to be able to enter my details in the form and get in

  Scenario: Login as admin user successfully
    Given I am a platform administrator
    Then I should not see an ".alert-danger" element

  Scenario: Create tests users successfully
    Given I am a platform administrator
    And I am on "/main/admin/filler.php?fill=users"
    Then I should not see an ".alert-danger" element

  Scenario: Login as student user successfully
    Given I am a student
    Then I should not see an ".alert-danger" element

  Scenario: Login as teacher successfully
    Given I am a teacher
    Then I should not see an ".alert-danger" element

  Scenario: Login as HRD successfully
    Given I am an HR manager
    Then I should not see an ".alert-danger" element

  Scenario: Login as student bott successfully
    Given I am a student boss
    Then I should not see an ".alert-danger" element

  Scenario: Login as invitee successfully
    Given I am an invitee
    Then I should not see an ".alert-danger" element
