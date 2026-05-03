Feature: Special admin settings flows — case 2
  In order to exercise several admin settings quickly
  As a platform administrator
  I want to run a few targeted scenarios that change multiple settings

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  Scenario: New user self-registration and first navigation

    # ---- INSCRIPTION ----
    # L'utilisateur non connecté arrive sur la page d'accueil et clique sur "Sign up"
    Given I am not logged
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "Sign up"
    When I follow "Sign up"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    # Le formulaire d'inscription est affiché (bouton "Register" visible)
    Then I should see "Register"

    # Champs de base
    And I fill in the following:
      | firstname                    | Test                      |
      | lastname                     | Learner                   |
      | email                        | parkur01@example.test |
      | username                     | parkur01              |
      | pass1                        | parkur01              |
      | pass2                        | parkur01              |
      | phone                        | 0600000000                |
      | extra_terms_adresse          | 10 rue de la Paix         |
      | extra_terms_codepostal       | 75001                     |
      | extra_terms_paysresidence    | France                    |
      | extra_terms_formation_niveau | Baccalaureat              |

    # Genre (radio)
    And I click the "input[name='extra_terms_genre[extra_terms_genre]'][value='homme']" element
    And I wait very long for the page to be loaded

    # Date de naissance (champ caché alimenté par le date picker)
    And I set hidden field "extra_terms_datedenaissance" to "1990-01-01"

    # Filière (radio)
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element
    And I wait very long for the page to be loaded

    # Langue interface : anglais (valeur = en_US)
    And I select "en_US" from "language"
    And I wait very long for the page to be loaded

    # Langue cible d'apprentissage : français
    And I select "french" from "extra_langue_cible"
    And I wait very long for the page to be loaded

    # Accepter les conditions d'utilisation
    And I click the "input[name='extra_platformuseconditions[extra_platformuseconditions]'][value='1']" element
    And I wait very long for the page to be loaded

    # Soumettre le formulaire
    And I press "Register"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- LIEN DIAGNOSTIC DANS LE MENU ----
    # La sidebar est déjà visible après inscription (pas besoin de chevron-up)
    # "Diagnosis management" est un panel PrimeVue sans href direct :
    # on clique le header pour déplier le sous-menu, puis on clique "Diagnosis"
    Then I should see "Diagnosis management"
    When I click the ".p-panelmenu-header[aria-label='Diagnosis management']" element
    And I wait very long for the page to be loaded
    When I follow "Diagnosis"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- MES SESSIONS ----
    # "My sessions" est un lien direct href="/sessions" dans la sidebar
    When I follow "My sessions"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"
    Then I should not see an error

    # ---- RÉSEAU SOCIAL ----
    And I am on "/social"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- MESSAGERIE ----
    And I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Admin creates tutors with language and assigns learner parkur01

    # ---- CRÉATION DES TUTEURS (TCs) AVEC LANGUE ASSIGNÉE ----
    # TODO: vérifier les extra fields langue disponibles sur user_add.php pour les teachers
    # (ex: extra_langue_cible, extra_langue_enseignee, etc.)

    # Tuteur 1 — langue française
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I fill in the following:
      | firstname | Tuteur    |
      | lastname  | Francais  |
      | email     | tuteur.fr@example.test |
      | username  | tuteur_fr |
      | password  | tuteur_fr |
    And I select "TEACHER" from "user_add_roles"
    # TODO: sélectionner la langue assignée au tuteur
    # And I select "french" from "extra_langue_enseignee"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Tuteur 2 — langue anglaise
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I fill in the following:
      | firstname | Tuteur   |
      | lastname  | Anglais  |
      | email     | tuteur.en@example.test |
      | username  | tuteur_en |
      | password  | tuteur_en |
    And I select "TEACHER" from "user_add_roles"
    # TODO: sélectionner la langue assignée au tuteur
    # And I select "english" from "extra_langue_enseignee"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SUIVI TC — ASSIGNATION DE L'APPRENANT AU TUTEUR ----
    # L'apprenant parkur01 a été créé dans le scénario précédent
    # TODO: confirmer l'URL exacte de la page "suivi TC" (ex: /main/mySpace/... ou /admin/...)
    # TODO: confirmer le chemin de menu "suivi / suivi / étoile / suivi TC"

    # And I am on "/TODO_suivi_tc_url"
    # And I wait very long for the page to be loaded

    # Admin sélectionne la langue de l'apprenant (french — langue cible de parkur01)
    # And I select "french" from "TODO_langue_field"
    # And I wait very long for the page to be loaded

    # Admin saisit les premières lettres du nom de l'apprenant et sélectionne parkur01
    # And I fill in "TODO_search_field" with "par"
    # And I wait very long for the page to be loaded
    # And I type and select "parkur01" in select2 field "TODO_field_id"
    # And I wait very long for the page to be loaded

    # Admin clique sur Ajouter
    # And I press "Ajouter"
    # And I wait very long for the page to be loaded
    # Then I should not see an error
