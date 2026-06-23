Feature: Special admin settings flows — case 2
  In order to exercise several admin settings quickly
  As a platform administrator
  I want to run a few targeted scenarios that change multiple settings

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  # ==============================================================
  # SCENARIO 1 — Parkur01 self-registration + post-registration navigation
  # ==============================================================
  Scenario: New user self-registration and first navigation

    Given I am not logged
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "Sign up"
    When I follow "Sign up"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Register"

    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname                    | Test                      |
      | lastname                     | Learner                   |
      | email                        | parkur01@example.test     |
      | username                     | parkur01                  |
      | pass1                        | parkur01                  |
      | pass2                        | parkur01                  |
      | phone                        | 0600000000                |
      | extra_terms_adresse          | 10 rue de la Paix         |
      | extra_terms_codepostal       | 75001                     |
      | extra_terms_paysresidence    | France                    |
      | extra_terms_formation_niveau | Baccalaureat              |

    # Genre (radio)
    And I wait for the element "input[name='extra_terms_genre[extra_terms_genre]'][value='homme']" to appear
    And I click the "input[name='extra_terms_genre[extra_terms_genre]'][value='homme']" element

    # Date of birth
    And I set hidden field "extra_terms_datedenaissance" to "1990-01-01"

    # Sector (radio)
    And I wait for the element "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" to appear
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element

    # Langue interface
    And I wait for the element "[name='language']" to appear
    And I select "en_US" from "language"

    # Target learning language
    And I wait for the element "[name='extra_langue_cible']" to appear
    And I select "french" from "extra_langue_cible"

    # Accepter les conditions
    And I wait for the element "input[name='extra_platformuseconditions[extra_platformuseconditions]'][value='1']" to appear
    And I click the "input[name='extra_platformuseconditions[extra_platformuseconditions]'][value='1']" element

    And I press "Register"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- DIAGNOSTIC LINK IN MENU ----
    Then I should see "Diagnosis management"
    And I wait for the element ".p-panelmenu-header[aria-label='Diagnosis management']" to appear
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

    # ---- DIAGNOSTIC FORM — Sector ----
    And I wait for the element "#card_filiere a" to appear
    And I click the "#card_filiere a" element
    And I wait for the element "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" to appear
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element
    And I wait for the element "[id='user_form_submit_partial[filiere]']" to appear
    And I click the "[id='user_form_submit_partial[filiere]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # Domains and theme
    And I wait for the element "#card_theme_obj a" to appear
    And I click the "#card_theme_obj a" element
    And I wait for the element "[name='extra_domaine_0']" to appear
    And I select "vie-quotidienne" from "extra_domaine_0"
    And I wait for the element "[name='extra_domaine_1']" to appear
    And I select "arrivee-sur-mon-poste-de-travail" from "extra_domaine_1"
    And I wait for the element "[name='extra_domaine_2']" to appear
    And I select "competente-dans-mon-domaine-de-specialite" from "extra_domaine_2"
    And I wait for the element "[name='extra_theme_fr_0']" to appear
    And I select "theme1" from "extra_theme_fr_0"
    And I wait for the element "[id='user_form_submit_partial[theme_obj]']" to appear
    And I click the "[id='user_form_submit_partial[theme_obj]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # Language level
    And I wait for the element "#card_niveau_langue a" to appear
    And I click the "#card_niveau_langue a" element
    And I wait for the element "[name='extra_ecouter']" to appear
    And I select "JePeuxComprendreLessentielDannoncesEtDeMessagesSimplesEtClairs" from "extra_ecouter"
    And I wait for the element "[name='extra_lire']" to appear
    And I select "JePeuxComprendreDesTextesCourtsTresSimplesEtTrouverUneInformationParticuliere" from "extra_lire"
    And I wait for the element "[name='extra_participer_a_une_conversation']" to appear
    And I select "JePeuxAvoirDesEchangesTresBrefsMemeSiEnGeneralJeNeComprendsPasAssezPourPoursuivreUneConversation" from "extra_participer_a_une_conversation"
    And I wait for the element "[name='extra_s_exprimer_oralement_en_continu']" to appear
    And I select "JePeuxUtiliserUneSerieDePhrasesOuDexpressionsPourDecrireSimplementMonEntourage" from "extra_s_exprimer_oralement_en_continu"
    And I wait for the element "[name='extra_ecrire']" to appear
    And I select "JePeuxEcrireUneLettrePersonnelleTresSimplePExDeRemerciements" from "extra_ecrire"
    And I wait for the element "[id='user_form_submit_partial[niveau_langue]']" to appear
    And I click the "[id='user_form_submit_partial[niveau_langue]']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    And I wait for the element "#user_form_submit" to appear
    And I click the "#user_form_submit" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- MY SESSIONS ----
    When I follow "My sessions"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"
    Then I should not see an error

    # ---- SOCIAL NETWORK ----
    And I am on "/social"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- MESSAGING ----
    And I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 2 — Admin creates tutors and assigns parkur01
  # ==============================================================
  Scenario: Admin creates tutors with language and assigns learner parkur01

    # Tutor 1 — French language
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname | Tuteur                 |
      | lastname  | Francais               |
      | email     | tuteur.fr@example.test |
      | username  | tuteur_fr              |
      | password  | tuteur_fr              |
    And I wait for the element "[name='user_add_roles']" to appear
    And I select "STUDENT_BOSS" from "user_add_roles"
    And I wait for the element "[name='user_edit_locale']" to appear
    And I select "fr_FR" from "user_edit_locale"
    And I wait for the element "input#send_mail_no" to appear
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Tutor 2 — English language
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "[name='firstname']" to appear
    And I fill in the following:
      | firstname | Tuteur                 |
      | lastname  | Anglais                |
      | email     | tuteur.en@example.test |
      | username  | tuteur_en              |
      | password  | tuteur_en              |
    And I wait for the element "[name='user_add_roles']" to appear
    And I select "STUDENT_BOSS" from "user_add_roles"
    And I wait for the element "[name='user_edit_locale']" to appear
    And I select "en_US" from "user_edit_locale"
    And I wait for the element "input#send_mail_no" to appear
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "The user has been added"

    # ---- TC FOLLOW-UP — Student's superior follow up ----
    When I am on "/main/my_space/index.php"
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-star-outline" to appear
    When I click the "i.mdi-star-outline" element
    And I wait very long for the page to be loaded
    When I follow "Student's superior follow up"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Tracking for superior"
    And I should not see an error

    # ---- LANGUAGE FILTER: French ----
    And I wait for the element "[name='language_filter_language']" to appear
    And I select "fr_FR" from "language_filter_language"
    And I wait for the element "em.mdi-magnify" to appear
    And I click the "em.mdi-magnify" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum

    # ---- ASSIGNMENT OF PARKUR01 TO FRENCH TUTOR ----
    And I wait for the element ".select2-selection__rendered" to appear
    When I click the ".select2-selection__rendered" element
    And I wait very long for the page to be loaded
    And I type and select "parkur01" in select2 field "dummy"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    And I should see "Test learner"
    Then I should not see an error

  # ==============================================================
  # SCENARIO 3 — Tuteur_fr checks the diagnosis and sends messages
  # ==============================================================
  Scenario: Tuteur_fr verifies diagnosis and sends messages

    Given I am not logged
    And I am logged as "tuteur_fr"
    And I wait very long for the page to be loaded

    # ---- MESSAGING: verify learner assignment ----
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "You have been assigned the learner Test Learner"
    When I follow "You have been assigned the learner Test Learner"
    And I wait very long for the page to be loaded
    Then I should see "http://127.0.0.1/main/my_space/myStudents.php?student=67"

    # ---- LEARNER PROFILE ----
    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    Then I should see "Test Learner"
    And I should see "Status"
    And I should see "Official code"
    And I should see "Tel"
    And I should see "Timezone"
    And I should see "Student's superior"

    # ---- DIAGNOSTIC PAGE ----
    When I am on "/main/search/load_search.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Load diagnosis"
    And I should not see an error

    And I wait for the element "em.mdi-magnify" to appear
    When I click the "em.mdi-magnify" element
    And I wait for the element "#card_theme_obj a" to appear
    And I click the "#card_theme_obj a" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "vie-quotidienne"
    And I should see "arrivee-sur-mon-poste-de-travail"
    And I should see "competente-dans-mon-domaine-de-specialite"
    And I should see "theme1"
    And I should see "french"

    # ---- SEND FINALIZATION MESSAGE ----
    When I follow "Send diagnostic finalization message"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- OPEN NEW MESSAGE FORM ----
    And I wait for the element "span.mdi-plus" to appear
    When I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- LEGAL AGREEMENT ----
    When I am on "/main/my_space/myStudents.php?action=send_legal&student=67&course="
    And I wait very long for the page to be loaded
    And I press "Send legal agreement"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- ASSIGNED SESSIONS ----
    When I am on "/main/search/load_search.php?user_id=67&save=&_qf__load="
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "i.mdi-plus" to appear
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "i.mdi-plus" to appear
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "i.mdi-plus" to appear
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "i.mdi-plus" to appear
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait for the element "i.mdi-plus" to appear
    And I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see 4 "i.mdi-delete" elements

  # ==============================================================
  # SCENARIO 4 — Admin checks sessions and creates a temptest session
  # ==============================================================
  Scenario: Admin validates sessions and creates temptest

    # Background: already logged in as admin — starting directly

    # ---- SESSION LIST ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Users"
    And I should see "Session Status"

    # ---- PRESENT SESSION ----
    When I follow "Present session"
    And I wait very long for the page to be loaded
    Then I should see "general coach Teacher Teacher"

    # ---- USER LIST ----
    When I am on "/admin/user-list"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-account-key" to appear
    When I click the "span.mdi-account-key" element
    And I wait very long for the page to be loaded

    # ---- ACCOUNT HOME PAGE ----
    When I am on "/account/home"
    And I wait very long for the page to be loaded
    Then I should see "Tuteur Anglais"

    # ---- LOGOUT AND LOGIN BACK AS ADMIN ----
    Given I am not logged
    And I am a platform administrator
    And I wait very long for the page to be loaded

    # ---- TC FOLLOW-UP ----
    When I am on "/main/my_space/index.php"
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-star-outline" to appear
    When I click the "i.mdi-star-outline" element
    And I wait very long for the page to be loaded
    When I follow "General Coaches planning"
    And I wait very long for the page to be loaded
    And I wait for the element "em.mdi-filter" to appear
    And I click the "em.mdi-filter" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "coach"
    And I should see "sessions"

    # ---- SESSION LIST — ADD ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-plus" to appear
    When I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded

    # ---- SESSION CREATION: step 1 (name + coach) ----
    And I wait for the element "[name='name']" to appear
    And I fill in "name" with "temptest"
    And I wait very long for the page to be loaded
    And I type and select "teacher" in select2 field "coach_username"
    And I wait very long for the page to be loaded
    And I wait for the element "em.mdi-arrow-right" to appear
    And I click the "em.mdi-arrow-right" element
    And I wait very long for the page to be loaded

    # ---- SESSION CREATION: step 2 (courses) ----
    Then I should see the ".select2-selection--multiple" element
    And I wait for the element "em.mdi-check" to appear
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should see the ".select2-selection--multiple" element
    And I wait for the element "em.mdi-check" to appear
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should see "Session overview"

    # ---- SOCIAL NETWORK ----
    When I am on "/social"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- DELETE TEMPTEST SESSION ----
    When I am on "/admin/session-list"
    And I wait very long for the page to be loaded
    And I wait for the element "[name='Search sessions']" to appear
    And I fill in "Search sessions" with "temptest"
    And I wait very long for the page to be loaded
    And I press "Search"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-delete" to appear
    When I click the "span.mdi-delete" element
    And I wait very long for the page to be loaded
    And I wait for the element ".p-confirmdialog-accept-button" to appear
    When I click the ".p-confirmdialog-accept-button" element
    And I wait very long for the page to be loaded

  # ==============================================================
  # SCENARIO 5 — Tutor assigns a skill and parkur01 does LP exercises
  # ==============================================================
  Scenario: Tuteur assigns skill and parkur01 completes exercises

    # ---- STUDENT FOLLOW-UP PAGE (admin via Background) ----
    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- SEND LEGAL AGREEMENT ----
    When I follow "Send legal agreement"
    And I wait very long for the page to be loaded
    Then I should not see an error

    When I am on "/main/my_space/myStudents.php?student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- OPEN SKILLS PANEL ----
    And I wait for the element "i.mdi-shield-star" to appear
    When I click the "i.mdi-shield-star" element
    And I wait very long for the page to be loaded
    Then I should see "Assign skill"

    And I wait for the element "[name='skill']" to appear
    And I select "NewSkill" from "skill"

    And I wait for the element "[name='argumentation']" to appear
    And I fill in "argumentation" with "test skills"

    And I wait for the element "[name='assign_skill_save']" to appear
    And I press "assign_skill_save"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- LOGIN AS PARKUR01 ----
    Given I am not logged
    And I am logged as "parkur01"
    And I wait very long for the page to be loaded

    # ---- INBOX ----
    And I wait for the element "i.mdi-inbox" to appear
    When I click the "i.mdi-inbox" element
    And I wait very long for the page to be loaded
    Then I should see "vous avez obtenu une nouvelle compétence"

    # ---- SIGN TERMS OF USE ----
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

    When I click element "div.flex.cursor-pointer" containing text "Present session"
    And I wait very long for the page to be loaded
    And I zoom out to maximum

    And I wait for the element "span[title='Testing course fr']" to appear
    When I click the "span[title='Testing course fr']" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- LEARNING PATH ----
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
    And I wait for the element "#choice-10-1" to appear
    And I click the "#choice-10-1" element
    And I wait for the element "[name='save_now']" to appear
    And I press "save_now"
    And I wait very long for the page to be loaded
    And I wait for the element ".p-radiobutton-icon" to appear
    And I click the ".p-radiobutton-icon" element
    And I wait for the element "[name='save_now']" to appear
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
    And I wait for the element "[name='save_now']" to appear
    And I press "save_now"
    And I wait very long for the page to be loaded
    And I switch back to the main window
    And I wait very long for the page to be loaded
    And I click element "a.items-list" containing text "final"
    And I wait very long for the page to be loaded
    Then I should see "100%"
    And I should not see an error

  # ==============================================================
  # SCENARIO 6 — Tutor deletes the legal agreement and generates the document
  # ==============================================================
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

  # ==============================================================
  # SCENARIO 7 — Teacher announcements, survey and video conference
  # ==============================================================
  Scenario: Teacher creates announcements, survey and videoconference

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
    And I wait for the element "i.mdi-bullhorn" to appear
    And I click the "i.mdi-bullhorn" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I wait for the element "[name='announcement_title']" to appear
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
    And I wait for the element "#announcement_preview" to appear
    When I click the "#announcement_preview" element
    And I wait very long for the page to be loaded
    Then I should see "Send announcement"
    And I should see "Test Learner"
    And I wait for the element "em.mdi-check" to appear
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- NEW ANNOUNCEMENT WITH DATE AND REMINDER ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    Then I should not see an error
    When I follow "Annonces"
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-bullhorn" to appear
    And I click the "i.mdi-bullhorn" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    And I wait for the element "[name='announcement_title']" to appear
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
    And I wait for the element "#announcement_preview" to appear
    When I click the "#announcement_preview" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I wait for the element "em.mdi-check" to appear
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SURVEY CREATION ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Enquêtes"
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-calendar-multiselect" to appear
    And I click the "i.mdi-calendar-multiselect" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Title"
    And I should see "Start Date"
    And I should see "End Date"
    And I should not see an error
    And I wait for the element "[name='survey_survey_title']" to appear
    And I fill in "survey_survey_title" with "Test survey"
    And I wait very long for the page to be loaded
    And I set flatpickr field "start_date" to "2026-06-02 08:00"
    And I set flatpickr field "end_date" to "2026-06-30 23:59"
    And I wait very long for the page to be loaded
    And I wait for the element "em.mdi-plus" to appear
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SURVEY EMAIL INVITATION ----
    And I wait for the element "i.mdi-email-alert" to appear
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
    And I wait for the element "[name='publish_form_mail_title']" to appear
    And I fill in "publish_form_mail_title" with "Test survey invitation"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "mail_text" with "Please take the survey."
    And I wait very long for the page to be loaded
    And I wait for the element "em.mdi-check" to appear
    When I click the "em.mdi-check" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SURVEY NAVIGATION ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Enquêtes"
    And I wait very long for the page to be loaded
    When I follow "Test survey"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- VIDEOCONFERENCE ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    When I follow "Vidéoconférence"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Copy text"
    And I should not see an error

  # ==============================================================
  # SCENARIO 8 — Parkur01 skills + teacher corrects the open question exercise
  # ==============================================================
  Scenario: Parkur01 skills review and teacher exercise correction

    Given I am not logged
    And I am logged as "parkur01"
    And I wait very long for the page to be loaded

    # ---- MY SKILLS ----
    When I am on "/main/social/my_skills_report.php"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "NewSkill"
    And I should not see an error

    # ---- SOCIAL NETWORK ----
    When I am on "/social"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-pencil" to appear
    And I click the "span.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "First name"
    And I should see "Last name"
    And I should see "E-mail"
    And I should see the "input#profile_illustration" element
    And I should not see an error

    # ---- INBOX ----
    When I am on "/resources/messages"
    And I wait very long for the page to be loaded
    When I follow "Vous avez obtenu une nouvelle compétence."
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "NewSkill"
    And I should see "/skill/2/user/67"
    And I should not see an error

    # ---- SKILL PAGE ----
    When I am on "/skill/2/user/67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Recipient details"
    And I should not see an error

    # ---- OPEN QUESTION EXERCISE ----
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
    And I wait for the element "[name='save_now']" to appear
    And I press "save_now"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- RECONNECT AS TEACHER ----
    Given I am not logged
    And I am logged as "teacher"
    And I wait very long for the page to be loaded

    # ---- INBOX ----
    And I wait for the element "i.mdi-inbox" to appear
    When I click the "i.mdi-inbox" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "A learner attempted an exercise"
    And I should not see an error

    # ---- COURSE FOLLOW-UP ----
    When I am on "/course/15/home?sid=1"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error
    And I wait for the element "#course-tool-404" to appear
    When I click the "#course-tool-404" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- LEARNER DETAILS ----
    When I am on "/main/my_space/myStudents.php?details=true&cid=15&course=TESTINGCOURSEFR&origin=tracking_course&sid=1&student=67"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should not see an error

    # ---- CORRECTION OPEN QUESTION ----
    And I wait for the element "i.mdi-order-bool-ascending-variant" to appear
    When I click the "i.mdi-order-bool-ascending-variant" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Open question exercise : Result"
    And I wait for the element "[name='show_ck']" to appear
    And I press "show_ck"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "comments_12" with "ZYX"
    And I wait very long for the page to be loaded
    And I wait for the element "input[name='send_notification']" to appear
    And I click the "input[name='send_notification']" element
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I wait for the element "em.mdi-send" to appear
    When I click the "em.mdi-send" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 9 — Admin calendar, social network, course creation and doodle
  # ==============================================================
  Scenario: Admin calendar events, social network, course and doodle

    # Background: already logged in as admin
    Then I should not see an error

    # ---- AGENDA ----
    When I follow "Agenda"
    And I wait very long for the page to be loaded
    Then I should see "Agenda"
    And I wait for the element "span.mdi-calendar-plus" to appear
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I wait for the element "[name='event-title']" to appear
    And I fill in "event-title" with "Evenement 4 jours"
    And I wait very long for the page to be loaded
    When I set datepicker "calendar-start-date" to "2026-06-15"
    And I set datepicker "calendar-end-date" to "2026-06-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement 4 jours"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I wait for the element "span.mdi-calendar-plus" to appear
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I wait for the element "[name='event-title']" to appear
    And I fill in "event-title" with "Evenement mois avant"
    And I wait very long for the page to be loaded
    When I set datepicker "calendar-start-date" to "2026-05-15"
    And I set datepicker "calendar-end-date" to "2026-06-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement mois avant"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I wait for the element "span.mdi-calendar-plus" to appear
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I wait for the element "[name='event-title']" to appear
    And I fill in "event-title" with "Evenement mois apres"
    And I wait very long for the page to be loaded
    When I set datepicker "calendar-start-date" to "2026-06-15"
    And I set datepicker "calendar-end-date" to "2026-07-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement mois apres"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I wait for the element "span.mdi-calendar-plus" to appear
    When I click the "span.mdi-calendar-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Add event"
    And I wait for the element "[name='event-title']" to appear
    And I fill in "event-title" with "Evenement avant et apres"
    And I wait very long for the page to be loaded
    When I set datepicker "calendar-start-date" to "2026-05-15"
    And I set datepicker "calendar-end-date" to "2026-07-18"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "calendar-event-content" with "Evenement avant et apres"
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- SOCIAL NETWORK — Home ----
    And I wait for the element "[aria-label='Social network']" to appear
    When I click the "[aria-label='Social network']" element
    And I wait very long for the page to be loaded
    And I wait for the element "a.p-menuitem-link[href='/social']" to appear
    And I click the "a.p-menuitem-link[href='/social']" element
    And I wait very long for the page to be loaded
    Then I should see "All Messages"
    And I should see "Promoted Messages"
    And I fill in tinymce field "content-editor" with "voici mon poste"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-send" to appear
    And I click the "span.mdi-send" element
    And I wait very long for the page to be loaded
    Then I should see "voici mon poste"
    And I should not see an error

    # ---- ADMINISTRATION — Course creation ----
    And I wait for the element "[aria-label='Administration']" to appear
    When I click the "[aria-label='Administration']" element
    And I wait very long for the page to be loaded
    And I wait for the element "a.p-menuitem-link[href='/admin']" to appear
    And I click the "a.p-menuitem-link[href='/admin']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    When I follow "Course list"
    And I wait very long for the page to be loaded
    And I wait for the element "span.mdi-plus" to appear
    And I click the "span.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I wait for the element "[name='title']" to appear
    And I fill in "title" with "Titre du cours"
    And I wait very long for the page to be loaded
    And I wait for the element "em.mdi-plus" to appear
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    Then I should see "Titre du cours"
    And I should not see an error

    # ---- SURVEY DOODLE ----
    And I wait for the element "[aria-label='Administration']" to appear
    When I click the "[aria-label='Administration']" element
    And I wait very long for the page to be loaded
    And I wait for the element "a.p-menuitem-link[href='/admin/course-list']" to appear
    And I click the "a.p-menuitem-link[href='/admin/course-list']" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    When I follow "Titre du cours"
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    When I follow "Surveys"
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-calendar-multiselect" to appear
    And I click the "i.mdi-calendar-multiselect" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I wait for the element "[name='survey_title']" to appear
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
    And I wait for the element "em.mdi-plus" to appear
    And I click the "em.mdi-plus" element
    And I wait very long for the page to be loaded
    Then I should see "Test Doodle"
    And I should not see an error

    # ---- PUBLISH DOODLE INVITATION ----
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I wait for the element "i.mdi-email-alert" to appear
    And I click the "i.mdi-email-alert" element
    And I wait very long for the page to be loaded
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I wait for the element "#users_rightAll" to appear
    And I click the "#users_rightAll" element
    And I wait very long for the page to be loaded
    And I wait for the element "[name='mail_title']" to appear
    And I fill in "mail_title" with "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    And I fill in tinymce field "mail_text" with "Vous etes invite a repondre a ce sondage."
    And I wait very long for the page to be loaded
    And I wait for the element "#publish_form_submit" to appear
    And I click the "#publish_form_submit" element
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- FILL THE DOODLE ----
    When I go to "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "Invitation Test Doodle"
    When I follow "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    When I go to "/main/survey/fillsurvey.php?iid=3&invitationcode=7a85590bfbe3238fc86d4fd214d99b3a&cid=21&course=TITREDUCOURS&sid=0&language=en_US"
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I wait for the element "a[href*='invitationcode'] i.mdi-pencil" to appear
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # ---- FILL DOODLE — 1st box ----
    And I wait for the element "a[href*='invitationcode'] i.mdi-pencil" to appear
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I check "1"
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see 1 element matching "i.mdi-check-circle.text-success"

    # ---- BACK TO MESSAGES — 2nd box ----
    When I go to "/resources/messages"
    And I wait very long for the page to be loaded
    Then I should see "Invitation Test Doodle"
    When I follow "Invitation Test Doodle"
    And I wait very long for the page to be loaded
    When I go to "/main/survey/fillsurvey.php?iid=3&invitationcode=7a85590bfbe3238fc86d4fd214d99b3a&cid=21&course=TITREDUCOURS&sid=0&language=en_US"
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I wait for the element "a[href*='invitationcode'] i.mdi-pencil" to appear
    When I click the "a[href*='invitationcode'] i.mdi-pencil" element
    And I wait very long for the page to be loaded
    And I check "2"
    And I wait very long for the page to be loaded
    And I press "Save"
    And I wait very long for the page to be loaded
    Then I should not see an error
    Then I should see 2 elements matching "i.mdi-check-circle.text-success"
