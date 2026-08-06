Feature: Special general case tests.

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  Scenario: Messaging to other user
    # Login as first student and open messaging
    Given I am not logged
    Then I am logged as "studentone"
    And I wait very long for the page to be loaded
    And I am on "resources/messages"
    And I wait for the element "span.mdi-email-plus-outline" to appear
    And I press "New message"
    And I wait very long for the page to be loaded
    And I should not see an error

    And I type character by character "StudentTwo" into field "to"
    And I wait up to 20 seconds for the element "li.p-autocomplete-option" to appear
    And I click the "li.p-autocomplete-option" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I am not logged


  Scenario: Registration and redirect
    # Verify that, when logged out, the homepage offers a "Sign up" button to main/auth/registration.php
    Given I am not logged
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "Inscription"
    When I follow "Inscription"
    And I wait very long for the page to be loaded
    Then I am on "main/auth/registration.php"
    And I wait very long for the page to be loaded
    And I should not see an error
    And I zoom out to maximum
    And I should not see "Follow courses"
    And I should not see "Teach courses"
    And I should see "E-mail"
    And I should see "Prénom"
    And I should see "Nom"
    And I should see "Nom d'utilisateur"
    And I should see "Mot de passe"
    And I should see "Confirmation du mot de passe"
    And I should see "Téléphone"
    And I should see "Langue"
    And I should see "Genre"
    And I should see "Date de naissance"
    And I should see "Nationalité"
    And I should see "Adresse"
    And I should see "Code postal"
    And I should see "Ville"
    And I should see "Pays de Résidence"
    And I should see "Langue cible d'apprentissage"
    And I should see "Je suis actuellement dans une filière ou je suis diplômé(e) d’une filière"
    And I should see "Dernier diplôme obtenu"
    And I should see "Ville du stage"
    And I should see "En cochant cette case, je confirme que j'accepte le traitement de mes données par l'OFAJ"
    And I should see "En cochant cette case je confirme que j'accepte les conditions d'utilisation de la plateforme Parkur"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded


  Scenario: Login and wrong login
    # Try to login with wrong credentials 6 times — blocked on last attempt
    Given I am not logged
    And I wait very long for the page to be loaded
    When I am on "/login"
    And I wait for the element "[name='login']" to appear
    And I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    And I wait for the element "[name='login']" to appear
    When I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    And I wait for the element "[name='login']" to appear
    When I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    And I wait for the element "[name='login']" to appear
    When I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    And I wait for the element "[name='login']" to appear
    When I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    And I wait for the element "[name='login']" to appear
    # 6th attempt — account should now be blocked
    When I fill in the following:
      | login    | acostea  |
      | password | wrongpwd |
    And I press "Connectez-vous"
    Then I should see "Identifiants invalides"
    # The blockage should persist for 5 minutes — correct password also rejected
    When I fill in the following:
      | login    | acostea  |
      | password | acostea  |
    And I press "Connectez-vous"
    Then I should see "Identifiants invalides"


  Scenario: Session list check specific users and tutors columns
    # Check numbers of user and teacher are present on session list page.
    Given I am not logged
    And I wait very long for the page to be loaded
    When I am logged as "admin"
    And I wait very long for the page to be loaded
    And I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    Then I should see "Users"
    And I should see "Tutors"


  Scenario: Skill, capability and Dimension assignment
    And I am on "main/skills/assign.php?user=1"
    And I wait for the element "[name='skill']" to appear
    When I select "Compétences en auto-apprentissage" from "skill"
    And I wait for the element "[name='sub_skill_id_1']" to appear
    When I select "Connaître ses forces et ses limites" from "sub_skill_id_1"
    And I wait very long for the page to be loaded
    Then I should see "Dimension"


  Scenario: Social wall post
    # Post on social page then verify like button (mdi-heart-plus) is visible
    And I am on "/social"
    And I wait very long for the page to be loaded
    Then I fill in tinymce field "content-editor" with "test"
    And I wait very long for the page to be loaded
    And I press "Post"
    And I wait very long for the page to be loaded
    Then I wait for the element "i.mdi-heart-plus" to appear
    And I should not see an error


  Scenario: profile, terms and menu
    Given I am not logged
    Then I am logged as "studentone"
    And I am on "/account/edit"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "First name"
    And I should see "Last name"
    And I should see "E-mail"
    And I should see "Picture"
    And I should see "Language"

    # Terms and redirect/default menu

    # NOTE: commented out — redirect_after_login=sessions, studenttwo is redirected
    And I am not logged
    Then I am logged as "studenttwo"
    And I am on "/main/auth/tc.php"
    And I wait very long for the page to be loaded
    Then I should see "The terms and conditions have not yet been validated by your tutor."

    # NOTE: commented out — redirect_after_login=sessions, studenttwo is redirected
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"
    Then I should not see "Terms and conditions"
    When I am on "/course/176/home"
    And I wait very long for the page to be loaded
    Then I should see "Terms and conditions"

