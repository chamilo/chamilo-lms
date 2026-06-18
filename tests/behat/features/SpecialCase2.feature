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
    And I zoom out to maximum
    Then I should see "Skills and objectives assessment"
    And I should see "I would like to choose a sector"
    And I should see "Availability before my internship/mobility"
    And I should see "Availability during my internship/mobility"
    And I should see "The topics that interest me / My learning objectives"
    And I should see "My language level"
    And I should see "My learning goals"
    And I should see "My working method"
    And I should see "My work environment"
    And I should not see an error

    # ---- FORMULAIRE DIAGNOSTIC ----

    # Filière : art-et-culture
    And I click the "#card_filiere a" element
    And I wait very long for the page to be loaded
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element
    And I wait very long for the page to be loaded
    And I click the "[id='user_form_submit_partial[filiere]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # Domaines et thème
    And I click the "#card_theme_obj a" element
    And I wait very long for the page to be loaded
    And I select "vie-quotidienne" from "extra_domaine_0"
    And I wait very long for the page to be loaded
    And I select "arrivee-sur-mon-poste-de-travail" from "extra_domaine_1"
    And I wait very long for the page to be loaded
    And I select "competente-dans-mon-domaine-de-specialite" from "extra_domaine_2"
    And I wait very long for the page to be loaded
    And I select "theme1" from "extra_theme_fr_0"
    And I wait very long for the page to be loaded
    And I click the "[id='user_form_submit_partial[theme_obj]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # Niveau de langue : deuxième option pour chaque compétence
    And I click the "#card_niveau_langue a" element
    And I wait very long for the page to be loaded
    And I select "JePeuxComprendreLessentielDannoncesEtDeMessagesSimplesEtClairs" from "extra_ecouter"
    And I wait very long for the page to be loaded
    And I select "JePeuxComprendreDesTextesCourtsTresSimplesEtTrouverUneInformationParticuliere" from "extra_lire"
    And I wait very long for the page to be loaded
    And I select "JePeuxAvoirDesEchangesTresBrefsMemeSiEnGeneralJeNeComprendsPasAssezPourPoursuivreUneConversation" from "extra_participer_a_une_conversation"
    And I wait very long for the page to be loaded
    And I select "JePeuxUtiliserUneSerieDePhrasesOuDexpressionsPourDecrireSimplementMonEntourage" from "extra_s_exprimer_oralement_en_continu"
    And I wait very long for the page to be loaded
    And I select "JePeuxEcrireUneLettrePersonnelleTresSimplePExDeRemerciements" from "extra_ecrire"
    And I wait very long for the page to be loaded
    And I click the "[id='user_form_submit_partial[niveau_langue]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # Envoyer le formulaire diagnostic
    And I click the "#user_form_submit" element
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
    And I select "STUDENT_BOSS" from "user_add_roles"
    And I select "fr_FR" from "user_edit_locale"
    And I wait very long for the page to be loaded
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
    And I select "STUDENT_BOSS" from "user_add_roles"
    And I select "en_US" from "user_edit_locale"
    And I wait very long for the page to be loaded
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "The user has been added"

    # ---- SUIVI TC — Student's superior follow up ----
    When I am on "/main/my_space/index.php"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-star-outline" element
    And I wait very long for the page to be loaded
    When I follow "Student's superior follow up"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Tracking for superior"
    And I should not see an error

    # ---- FILTRE PAR LANGUE : français ----
    And I select "fr_FR" from "language_filter_language"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum

    # ---- ASSIGNATION DE PARKUR01 AU TUTEUR FRANCAIS ----
    # Le select2 est en AJAX (ID dynamique) : on ouvre le dropdown via le span,
    # puis on tape "parkur01" pour déclencher la recherche et on clique le résultat
    When I click the ".select2-selection__rendered" element
    And I wait very long for the page to be loaded
    And I type and select "parkur01" in select2 field "dummy"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    And I should see "Test learner"
    Then I should not see an error

    # ---- VIDEOCONFERENCE (BBB) ----
    # TODO: vérifier que l'icône Videoconference apparaît dans le menu du cours
    # Prérequis : plugin BBB activé + host/salt configurés + région "Course tool" assignée
    # When I am on "/courses/XXX/home"
    # And I wait very long for the page to be loaded
    # Then I should see "Videoconference"

  Scenario: Tuteur_fr opens diagnosis page and sends finalization message

    # ---- CONNEXION EN TANT QUE TUTEUR_FR ----
    Given I am not logged
    And I am logged as "tuteur_fr"
    And I wait very long for the page to be loaded

    # ---- MESSAGERIE : vérification assignation apprenant ----
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "You have been assigned the learner Test Learner"
    When I follow "You have been assigned the learner Test Learner"
    And I wait very long for the page to be loaded
    Then I should see "http://127.0.0.1/main/my_space/myStudents.php?student=67"

    # ---- FICHE APPRENANT ----
    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    Then I should see "Test Learner"
    And I should see "Status"
    And I should see "Official code"
    And I should see "Tel"
    And I should see "Timezone"
    And I should see "Student's superior"

    # ---- PAGE DIAGNOSTIC ----
    When I am on "/main/search/load_search.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Load diagnosis"
    And I should not see an error

    # ---- OUVRIR LE PANNEAU DE RECHERCHE ----
    When I click the "em.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I click the "#card_theme_obj a" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "vie-quotidienne"
    And I should see "arrivee-sur-mon-poste-de-travail"
    And I should see "competente-dans-mon-domaine-de-specialite"
    And I should see "theme1"
    And I should see "french"

    # ---- ENVOYER LE MESSAGE DE FINALISATION ----
    When I follow "Send diagnostic finalization message"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- OUVRIR LE FORMULAIRE DE NOUVEAU MESSAGE ----
    When I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- ACCORD LÉGAL ----
    When I am on "/main/my_space/myStudents.php?action=send_legal&student=67&course="
    And I wait very long for the page to be loaded
    And I press "Send legal agreement"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SESSIONS ASSIGNÉES ----
    When I am on "/main/search/load_search.php?user_id=67&save=&_qf__load="
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see 4 "i.mdi-delete" elements

    # ---- DÉCONNEXION ET RECONNEXION EN TANT QU'ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait very long for the page to be loaded

    # ---- LISTE DES SESSIONS ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Users"
    And I should see "Session Status"

    # ---- SESSION PRÉSENTE ----
    When I follow "Present session"
    And I wait very long for the page to be loaded
    Then I should see "general coach Teacher Teacher"

    # ---- LISTE DES UTILISATEURS ----
    When I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    When I click the "span.mdi-account-key" element
    And I wait very long for the page to be loaded

    # ---- PAGE D'ACCUEIL DU COMPTE ----
    When I am on "/account/home"
    And I wait very long for the page to be loaded
    Then I should see "Tuteur Anglais"

    # ---- DÉCONNEXION ET RECONNEXION EN TANT QU'ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait very long for the page to be loaded

    # ---- SUIVI TC ----
    When I am on "/main/my_space/index.php"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-star-outline" element
    And I wait very long for the page to be loaded
    When I follow "General Coaches planning"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-filter" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "coach"
    And I should see "sessions"

    # ---- LISTE DES SESSIONS — AJOUTER ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    When I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded

    # ---- CRÉATION DE SESSION : étape 1 (nom + coach) ----
    And I fill in "name" with "temptest"
    And I wait very long for the page to be loaded
    And I type and select "teacher" in select2 field "coach_username"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-arrow-right" element
    And I wait very long for the page to be loaded

    # ---- CRÉATION DE SESSION : étape 2 (cours) ----
    Then I should see the ".select2-selection--multiple" element
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should see the ".select2-selection--multiple" element
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should see "Session overview"

    # ---- RÉSEAU SOCIAL ----
    When I am on "/social"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SUPPRESSION DE LA SESSION TEMPTEST ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    And I fill in "Search sessions" with "temptest"
    And I wait very long for the page to be loaded
    And I press "Search"
    And I wait very long for the page to be loaded
    When I click the "span.mdi-delete" element
    And I wait very long for the page to be loaded
    When I click the ".p-confirmdialog-accept-button" element
    And I wait very long for the page to be loaded

  Scenario: Tuteur_fr visits student report and sends legal agreement

    # ---- PAGE SUIVI ÉTUDIANT ----
    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- ENVOYER L'ACCORD LÉGAL ----
    When I follow "Send legal agreement"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- RETOUR PAGE SUIVI ÉTUDIANT ----
    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- OUVRIR LE PANNEAU COMPÉTENCES ----
    When I click the "i.mdi-shield-star" element
    And I wait very long for the page to be loaded
    Then I should see "Assign skill"

    # ---- SÉLECTIONNER LA COMPÉTENCE ----
    And I select "NewSkill" from "skill"
    And I wait very long for the page to be loaded

    # ---- SAISIR L'ARGUMENTATION ----
    And I fill in "argumentation" with "test skills"
    And I wait very long for the page to be loaded

    # ---- SAUVEGARDER ----
    And I press "assign_skill_save"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- CONNEXION EN TANT QUE PARKUR01 ----
    Given I am not logged
    And I am logged as "parkur01"
    And I wait very long for the page to be loaded

    # ---- BOÎTE DE RÉCEPTION ----
    When I click the "i.mdi-inbox" element
    And I wait very long for the page to be loaded
    Then I should see "vous avez obtenu une nouvelle compétence"

    # ---- SIGNATURE DES CGU ----
    When I am on "/main/auth/tc.php"
    And I wait very long for the page to be loaded
    And I press "Accept Terms and Conditions"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SESSIONS ----
    When I am on "/sessions"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Present session"

    # Ouvrir le chevron de la session
    When I click element "div.flex.cursor-pointer" containing text "Present session"
    And I wait very long for the page to be loaded
    And I zoom out to maximum

    # Cliquer sur le cours
    When I click the "span[title='Testing course fr']" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Naviguer vers le LP
    And I am on "/course/15/home"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "LP Test"
    And I wait very long for the page to be loaded

    # QRU and Image Selection exercise
    And I click element "a.items-list" containing text "QRU and Image Selection exercise"
    And I wait very long for the page to be loaded
    And I switch to the iframe "content_name"
    And I wait very long for the page to be loaded
    When I follow "Start test"
    And I wait very long for the page to be loaded
    And I click the "#choice-10-1" element
    And I wait very long for the page to be loaded
    And I press "save_now"
    And I wait very long for the page to be loaded
    And I click the ".p-radiobutton-icon" element
    And I wait very long for the page to be loaded
    And I press "save_now"
    And I wait very long for the page to be loaded
    And I switch back to the main window
    And I wait very long for the page to be loaded

    # Open question exercise
    And I click element "a.items-list" containing text "Open question exercise"
    And I wait very long for the page to be loaded
    And I switch to the iframe "content_name"
    And I wait very long for the page to be loaded
    When I follow "Start test"
    And I wait very long for the page to be loaded
    And I fill in the first textarea with "example"
    And I press "save_now"
    And I wait very long for the page to be loaded
    And I switch back to the main window
    And I wait very long for the page to be loaded
    And I click element "a.items-list" containing text "final"
    And I wait very long for the page to be loaded
    Then I should see "100%"
    And I should not see an error

  Scenario: Tuteur deletes legal agreement and generates document

    Given I am not logged
    And I am logged as "tuteur_fr"
    And I wait very long for the page to be loaded
    And I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    Then I should see "Delete legal agreement"
    When I follow "Generate"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Teacher navigates sessions and course announcements

    Given I am not logged
    And I am logged as "teacher"
    And I wait very long for the page to be loaded
    And I am on "/sessions/past"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I am on "/sessions"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I follow "Annonces"
    And I wait very long for the page to be loaded
    And I click the "i.mdi-bullhorn" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I fill in "announcement_title" with "Test announcement"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "content" with "Test announcement content"
    And I wait very long for the page to be loaded
    And I press "choose_recipients"
    And I wait very long for the page to be loaded
    Then I should see "users"
    Then I should see "Test Learner"
    And I press "choose_recipients"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I should see "Send this announcement by email to selected groups/users"
    And I should see "description"
    And I should see "Send a copy by email to myself"
    When I click the "#announcement_preview" element
    And I wait very long for the page to be loaded
    Then I should see "Send announcement"
    And I should see "Test Learner"
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- NOUVELLE ANNONCE AVEC DATE ET RAPPEL ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I follow "Annonces"
    And I wait very long for the page to be loaded
    And I click the "i.mdi-bullhorn" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    And I fill in "announcement_title" with "Test announcement"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "content" with "Test announcement content"
    And I wait very long for the page to be loaded
    And I press "add_event"
    And I wait very long for the page to be loaded
    And I set flatpickr field "event_date_start" to "2026-06-02 08:00:00"
    And I set flatpickr field "event_date_end" to "2026-06-30 23:59:00"
    And I wait very long for the page to be loaded
    And I press "announcement_add_notification"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I click the "#announcement_preview" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- CRÉATION D'ENQUÊTE ----
      When I am on "/course/15/home?sid=1"
      And I wait very long for the page to be loaded
      And I zoom out to maximum
      Then I should not see an error
      When I follow "Enquêtes"
    And I wait very long for the page to be loaded
    And I click the "i.mdi-calendar-multiselect" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Title"
    And I should see "Start Date"
    And I should see "End Date"
    And I should not see an error
    And I fill in "survey_survey_title" with "Test survey"
    And I wait very long for the page to be loaded
    And I set flatpickr field "start_date" to "2026-06-02 08:00"
    And I set flatpickr field "end_date" to "2026-06-30 23:59"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- ENVOI EMAIL ENQUÊTE ----
    When I click the "i.mdi-email-alert" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Users"
    And I should see "Test Learner"
    And I should see "Remind all users of the survey"
    And I should see "Remind only users who didn't answer"
    And I should see "Hide survey invitation link"
    And I should see "Users who are not invited can use this link to take the survey:"
    And I should not see an error
    And I fill in "publish_form_mail_title" with "Test survey invitation"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "mail_text" with "Please take the survey."
    And I wait very long for the page to be loaded
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- NAVIGATION ENQUÊTE ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Enquêtes"
    And I wait very long for the page to be loaded
    When I follow "Test survey"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- VIDÉOCONFÉRENCE ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Vidéoconférence"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Copy text"
    And I should not see an error

    # ---- CONNEXION EN TANT QUE PARKUR01 ----
    Given I am not logged
    And I am logged as "parkur01"
    And I wait very long for the page to be loaded

    # ---- MY SKILLS ----
    When I am on "/main/social/my_skills_report.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "NewSkill"
    And I should not see an error

    # ---- RÉSEAU SOCIAL ----
    When I am on "/social"
    And I wait very long for the page to be loaded
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "First name"
    And I should see "Last name"
    And I should see "E-mail"
    And I should see the "input#profile_illustration" element
    And I should not see an error

    # ---- BOÎTE DE RÉCEPTION ----
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    When I follow "Vous avez obtenu une nouvelle compétence."
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "NewSkill"
    And I should see "/skill/2/user/67"
    And I should not see an error

    # ---- PAGE COMPÉTENCE ----
    When I am on "/skill/2/user/67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Recipient details"
    And I should not see an error

    # ---- EXERCICE OPEN QUESTION ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Exercices"
    And I wait very long for the page to be loaded
    When I follow "Open question exercise"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    When I follow "Start test"
    And I wait very long for the page to be loaded
    And I fill in the first textarea with "example"
    And I press "save_now"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- RECONNEXION EN TANT QUE TEACHER ----
    Given I am not logged
    And I am logged as "teacher"
    And I wait very long for the page to be loaded

    # ---- BOÎTE DE RÉCEPTION ----
    When I click the "i.mdi-inbox" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "A learner attempted an exercise"
    And I should not see an error

    # ---- SUIVI COURS ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I click the "#course-tool-404" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- DÉTAILS APPRENANT ----
    When I am on "/main/my_space/myStudents.php?details=true&cid=15&course=TESTINGCOURSEFR&origin=tracking_course&sid=1&student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- CORRECTION OPEN QUESTION ----
    When I click the "i.mdi-order-bool-ascending-variant" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Open question exercise : Result"
    And I press "show_ck"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "comments_12" with "ZYX"
    And I wait very long for the page to be loaded
    And I click the "input[name='send_notification']" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I should not see an error
    When I click the "em.mdi-send" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- CONNEXION EN TANT QU'ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- AGENDA ----
    When I follow "Agenda"
    And I wait very long for the page to be loaded
    Then I should see "Agenda"
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I fill in "event-title" with "Evenement 4 jours"
    And I wait very long for the page to be loaded
    When I set primevue datepicker "calendar-start-date" to "2026-06-15"
    And I set primevue datepicker "calendar-end-date" to "2026-06-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement 4 jours"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- DEUXIÈME ÉVÉNEMENT : mois précédent → mois en cours ----
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I fill in "event-title" with "Evenement mois avant"
    And I wait very long for the page to be loaded
    When I set primevue datepicker "calendar-start-date" to "2026-05-15"
    And I set primevue datepicker "calendar-end-date" to "2026-06-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement mois avant"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- TROISIÈME ÉVÉNEMENT : mois en cours → mois suivant ----
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I fill in "event-title" with "Evenement mois apres"
    And I wait very long for the page to be loaded
    When I set primevue datepicker "calendar-start-date" to "2026-06-15"
    And I set primevue datepicker "calendar-end-date" to "2026-07-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement mois apres"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- QUATRIÈME ÉVÉNEMENT : mois précédent → mois suivant ----
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I fill in "event-title" with "Evenement avant et apres"
    And I wait very long for the page to be loaded
    When I set primevue datepicker "calendar-start-date" to "2026-05-15"
    And I set primevue datepicker "calendar-end-date" to "2026-07-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement avant et apres"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SOCIAL NETWORK — Home ----
    When I click the "[aria-label='Social network']" element
    And I wait very long for the page to be loaded
    And I click the "a.p-menuitem-link[href='/social']" element
    And I wait very long for the page to be loaded
    Then I should see "All Messages"
    And I should see "Promoted Messages"
    And I fill in tinymce field "content-editor" with "voici mon poste"
    And I wait very long for the page to be loaded
    And I click the "span.mdi-send" element
    And I wait very long for the page to be loaded
    Then I should see "voici mon poste"
    And I should not see an error

    # ---- ADMINISTRATION ----
    When I click the "[aria-label='Administration']" element
    And I wait very long for the page to be loaded
    And I click the "a.p-menuitem-link[href='/admin']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I follow "Course list"
    And I wait very long for the page to be loaded
    And I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I fill in "title" with "Titre du cours"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Titre du cours"
    And I should not see an error

    # ---- SURVEY DOODLE ----
    When I click the "[aria-label='Administration']" element
    And I wait very long for the page to be loaded
    And I click the "a.p-menuitem-link[href='/admin/course-list']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I follow "Titre du cours"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I follow "Surveys"
    And I wait very long for the page to be loaded
    And I click the "i.mdi-calendar-multiselect" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I fill in "survey_title" with "Test Doodle"
    And I wait very long for the page to be loaded
    And I set flatpickr field "start_date" to "2026-06-07"
    And I wait very long for the page to be loaded
    And I set flatpickr field "end_date" to "2026-06-14"
    And I wait very long for the page to be loaded
    And I set flatpickr field "time_1" to "2026-06-08"
    And I wait very long for the page to be loaded
    And I set flatpickr field "time_2" to "2026-06-09"
    And I wait very long for the page to be loaded
    And I set flatpickr field "time_3" to "2026-06-11"
    And I wait very long for the page to be loaded
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Test Doodle"
    And I should not see an error

    # ---- PUBLIER L'INVITATION DOODLE ----
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I click the "i.mdi-email-alert" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I click the "#users_rightAll" element
    And I wait very long for the page to be loaded
    And I wait very long for the page to be loaded
    And I fill in "mail_title" with "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "mail_text" with "Vous etes invite a repondre a ce sondage."
    And I wait very long for the page to be loaded
    And I click the "#publish_form_submit" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- REMPLIR LE DOODLE ----
    When I go to "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "Invitation Test Doodle"
    When I follow "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    # When I follow "Click here to answer the survey"
    # (lien direct dans le mail)
    When I go to "/main/survey/fillsurvey.php?iid=3&invitationcode=7a85590bfbe3238fc86d4fd214d99b3a&cid=21&course=TITREDUCOURS&sid=0&language=en_US"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- REMPLIR LE DOODLE — cocher la 1ère case ----
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I check "1"
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see 1 element matching "i.mdi-check-circle.text-success"

    # ---- RETOUR MESSAGES — cocher la 1ère case non cochée ----
    When I go to "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "Invitation Test Doodle"
    When I follow "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    # When I follow "Click here to answer the survey"
    # (lien direct dans le mail)
    When I go to "/main/survey/fillsurvey.php?iid=3&invitationcode=7a85590bfbe3238fc86d4fd214d99b3a&cid=21&course=TITREDUCOURS&sid=0&language=en_US"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I check "2"
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see 2 elements matching "i.mdi-check-circle.text-success"

