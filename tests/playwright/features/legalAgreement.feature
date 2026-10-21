Feature: Legal agreement sending
  In order to have learners sign the terms and conditions
  As a platform administrator
  I need to send the legal agreement to a learner from the user information page and the
  learner reporting, with the same message in both cases

  # Deleting an acceptance is not covered here: it needs the learner to accept a version of
  # the terms and conditions first, and adding one would make every other test account accept
  # it at its next login.
  #
  # Both send buttons only exist when allow_terms_conditions is Yes, and its default is No.
  # Turning it on does not affect the other files running in parallel: with no terms and
  # conditions text, the login does not ask anyone to accept them (SecurityController).

  Scenario: Admin enables the terms and conditions
    Given I am a platform administrator
    And I am on "/admin/settings/search_settings?keyword=allow_terms_conditions"
    And I wait for the page to be loaded
    And I select "Yes" from "form_allow_terms_conditions"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin creates the learner
    Given I am a platform administrator
    When I am on "/admin/user-add"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Legal                      |
      | lastname  | Learner                    |
      | email     | legal.learner@example.test |
      | username  | legal_learner              |
    And I check the "Set password manually" radio button
    And I fill in "password" with "LegalLearner01!"
    And I check the "No" radio button
    And I press "Add"
    And wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Admin sends the legal agreement from the user information page
    Given I am a platform administrator
    And I resolve the user id for "legal_learner"
    When I am on "/main/admin/user_information.php?user_id=USER_ID" with the resolved user id
    And I wait for the page to be loaded
    And I click the "form[action$='/send'] button" element
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "legal.learner@example.test"

  Scenario: The learner receives the message sent from the learner tracking page
    Given I am not logged
    And I am logged as "legal_learner" with password "LegalLearner01!"
    And I wait for the page to be loaded
    When I am on "/resources/messages"
    And I wait for the page to be loaded
    And I wait up to 30 seconds for the element "text=Your terms and conditions are ready to be signed" to appear
    Then I should not see "Please accept our legal conditions here"
    # The message is addressed to the learner by first name and signed by the sender.
    When I click the "a:has-text('Your terms and conditions are ready to be signed')" element
    Then I wait up to 30 seconds for the element "text=Hello Legal," to appear
    And I should see "Your tutor sent you your terms and conditions"
    And I should see "John Doe"

  Scenario: Admin sends the legal agreement from the learner reporting
    Given I am a platform administrator
    And I resolve the user id for "legal_learner"
    When I am on "/reporting/learners/USER_ID" with the resolved user id
    And I wait for the page to be loaded
    And I press "Send legal agreement"
    Then I wait up to 30 seconds for the element "text=Sent" to appear

  @long-scenario
  Scenario: A learner cannot reach the user information page
    Given I am a platform administrator
    And I resolve the user id for "legal_learner"
    And I am not logged
    And I am logged as "legal_learner" with password "LegalLearner01!"
    And I wait for the page to be loaded
    When I am on "/main/admin/user_information.php?user_id=USER_ID" with the resolved user id
    And I wait for the page to be loaded
    Then I should not see "Send legal agreement"

  @long-scenario
  Scenario: Teardown — delete the learner
    Given I am a platform administrator
    When I am on "/admin/user-list?keyword=legal_learner"
    And I wait for the page to be loaded
    And I click the "[title='Delete']" icon in the row for "legal.learner@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "legal.learner@example.test"

    When I am on "/admin/user-list?view=deleted&keyword=legal_learner"
    And I wait for the page to be loaded
    And I click the "[title='Delete permanently']" icon in the row for "legal.learner@example.test"
    And I press "Yes"
    And I wait for the page to be loaded
    Then I should not see "legal.learner@example.test"

  Scenario: Teardown — disable the terms and conditions again
    Given I am a platform administrator
    When I am on "/admin/settings/search_settings?keyword=allow_terms_conditions"
    And I wait for the page to be loaded
    And I select "No" from "form_allow_terms_conditions"
    And I press "Save settings"
    And I wait for the page to be loaded
    Then I should not see an error
