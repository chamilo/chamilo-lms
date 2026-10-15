# Ported from tests/behat/features/SpecialCase/newPlatform/SpecialCase2.feature
# (git show 98c77757ea6:tests/behat/features/SpecialCase/newPlatform/SpecialCase2.feature —
# the commit right before Behat was removed, per CLAUDE.md's own pointer).
# Covers only that file's first scenario ("New user self-registration and
# first navigation"). The other 5 scenarios (tutor creation/assignment,
# diagnosis/legal-agreement workflow, learning-path exercises, teacher
# announcements/surveys/agenda) belong to two separate, concurrently-planned
# files (specialCase2TutorWorkflow.feature, specialCase2TeacherTools.feature)
# and are out of scope here.
#
# Every selector below was verified LIVE against testparkur.beeznest.com (not
# ported blind) — see the findings below for what changed vs the Behat
# source and why.
#
# REAL PLATFORM BUG FOUND AND FIXED WHILE PORTING THIS SCENARIO:
# /main/auth/registration.php returned a bare HTTP 500 (empty body) the first
# time this was attempted live. Root-caused via the Apache error log (not
# guessed): FormValidator::addGeoLocationMapField() reads
# $googleMapsPlugin->javascriptIncluded before it is ever written — a
# property never declared on GoogleMapsPlugin, only ever assigned dynamically
# — which is a fatal "Undefined property" on PHP 8.2+ once this app's error
# handler escalates it. Fixed by declaring the property with its intended
# default (public/plugin/GoogleMaps/src/GoogleMapsPlugin.php). This step
# would fail identically against ANY Chamilo install with the Google Maps
# plugin's `enable_api` setting on and at least one Geolocalization-type
# extra field on the registration form (this platform's "extra_terms_ville"
# field) — not something specific to this one box.
#
# REGISTRATION FORM — FULLY RE-DISCOVERED, NOT PORTED BLIND:
# - The Behat source's own field list (firstname/lastname/email/username/
#   pass1/pass2/phone plus extra_terms_* fields) is still accurate in
#   substance, but several details had rotted:
#   - "pass1"/"pass2" are real ids (confirmed live), matched by resolveField's
#     id tier — no change needed there.
#   - extra_terms_ville (City) is the Geolocalization-type field that hit the
#     bug above. It renders as a plain text input beside "Search for this
#     location"/"My location" buttons; filling the visible text field alone
#     (no need to trigger the map widget) is enough to pass validation —
#     confirmed live, no coordinates required server-side.
#   - extra_langue_cible ("Langue cible d'apprentissage") is a real <select>,
#     but its options are NOT "french"/"german" as the Behat source assumed —
#     confirmed live via the rendered <option> list: "French2" and "German2"
#     (a platform-specific naming quirk, not a typo in this port).
#   - extra_filiere_user (sector) genuinely still has an "art et culture"
#     option with value "art-et-culture", matching the Behat source exactly.
#   - extra_terms_genre's "homme" (Monsieur) value also still matches.
# - Radio-group and checkbox ids (genre, filiere_user, gdpr,
#   platformuseconditions) are SERVER-GENERATED PER PAGE LOAD
#   (FormValidator's `qf_<random>` scheme) — confirmed live by reloading the
#   page twice and diffing the ids. Never hardcode one; always target by
#   `[name="..."][value="..."]`, which is what the Behat source already did
#   for the radios (kept as-is) and what this port adds for the two
#   checkboxes (the source only clicked platformuseconditions; gdpr is
#   checked too here since nothing in the source explains omitting it and
#   leaving a real consent checkbox unchecked is not a meaningful test of
#   the form).
#
# DIAGNOSIS FORM — STRUCTURE CONFIRMED STILL ACCURATE, SELECTORS KEPT:
# - /main/search/search.php renders one legacy FormValidator form
#   ("user_form") as 9 collapsed accordion cards; EVERY field across every
#   card is already in the DOM on load (confirmed live via a full-page field
#   dump) — only visibility toggles per card, so there is no lazy-render race
#   to wait out, just the card's own header click.
# - Each card header is a plain, class-only <div> (no role, no href) —
#   "I click element '...' containing text '...'" (new step, this file) is
#   the only way to target it; "I follow" would never match (see that step's
#   own comment in common.steps.ts).
# - extra_domaine_0/1/2 and extra_theme_fr_0 through _4 are real, stable ids
#   confirmed live — matching the Behat source exactly, because these are
#   the SAME session extra fields this suite's specialCase1Sessions.feature
#   already created (domaine, theme_fr), just rendered here as one ranked
#   <select> per priority slot instead of session_edit.php's single
#   Select2 multi-select. "vie-quotidienne" / "arrivee-sur-mon-poste-de-
#   travail" / "competente-dans-mon-domaine-de-specialite" and "theme1" are
#   the same option values created there.
# - extra_ecouter/lire/participer_a_une_conversation/
#   s_exprimer_oralement_en_continu/ecrire are plain <select multiple>s here
#   (not the ajax tag widget seen elsewhere) — selecting by the exact option
#   LABEL text (the long French CEFR-style sentence) is reliable and matches
#   the Behat source's own choice of value.
# - The per-card "Save" buttons' ids
#   (`user_form_submit_partial[filiere]`, `[theme_obj]`, `[niveau_langue]`)
#   and the final `user_form_submit` ("Send") button are STILL EXACTLY what
#   the Behat source used — confirmed live via a full button-id dump. This
#   part of the source aged the best of anything in this file.
#
# NOT PORTED FROM THIS SCENARIO, MATCHING THE SOURCE'S OWN SCOPE:
# - The Behat source's own commented-out Videoconference/BBB check (its
#   prerequisites — plugin enabled, host/salt configured — are out of scope
#   for a plain registration smoke test) is left out here too.
#
# SELF-CONTAINMENT: teardown for the account created here (parkur01) is
# deliberately NOT in this file. specialCase2TutorWorkflow.feature depends on
# this exact account existing (the tutor-assignment and diagnosis scenarios
# there act on it), so cleanup is centralized in that file's own teardown
# scenario rather than duplicated/raced here.
@common @long-scenario @specialcases @specialcase2
Feature: Special case 2 — self-registration and diagnosis
  In order to validate a realistic self-registration and diagnosis flow
  As a new platform user
  I need to register, fill in the diagnosis questionnaire, and reach my sessions

  # The registration form this file drives depends on platform settings that
  # specialCase1PlatformSettings' teardown leaves in a different state (registration by
  # approval, terms on, no registration fields), so they are set here, not assumed.
  # Terms are off because the "Validate" button only exists then (registration.php).
  Scenario: Configure the registration settings
    Given I am a platform administrator
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_registration"
    And I wait for the page to be loaded
    And I select "Yes" from "form_allow_registration"
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_terms_conditions"
    And I wait for the page to be loaded
    And I select "No" from "form_allow_terms_conditions"
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_fields_inscription"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_allow_fields_inscription | {"fields":["lastname","firstname","email","language","phone","address"],"extra_fields":["terms_nationalite","terms_numeroderue","terms_nomderue","terms_codepostal","terms_paysresidence","terms_ville","terms_datedenaissance","terms_genre","filiere_user","terms_formation_niveau","terms_villedustage","terms_adresse","gdpr","platformuseconditions","langue_cible"]} |
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=required_extra_fields_in_inscription"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_required_extra_fields_in_inscription | {"options":["terms_adresse","terms_codepostal","terms_ville","terms_paysresidence","terms_datedenaissance","terms_genre","filiere_user","terms_formation_niveau","gdpr","platformuseconditions","langue_cible"]} |
    And I press "Save settings"
    And I wait for the page to be loaded

  Scenario: New user self-registration, diagnosis form, and first navigation
    Given I am not logged
    And I am on "/home"
    And I wait for the page to be loaded
    Then I should see "Sign up"

    When I follow "Sign up"
    And I wait for the page to be loaded
    Then I should see "Validate"

    And I fill in the following:
      | email                        | parkur01@example.test |
      | firstname                    | Test                  |
      | lastname                     | Learner                |
      | username                     | parkur01               |
      | pass1                        | Parkur01Test!           |
      | pass2                        | Parkur01Test!           |
      | phone                        | 0600000000              |
      | extra_terms_adresse          | 10 rue de la Paix       |
      | extra_terms_codepostal       | 75001                   |
      | extra_terms_ville            | Paris                   |
      | extra_terms_paysresidence    | France                  |
      | extra_terms_formation_niveau | Baccalaureat            |
    And I click the "input[name='extra_terms_genre[extra_terms_genre]'][value='homme']" element
    And I set hidden field "extra_terms_datedenaissance" to "1990-01-01"
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element
    And I select "French2" from "extra_langue_cible"
    And I click the "input[name='extra_gdpr[extra_gdpr]']" element
    And I click the "input[name='extra_platformuseconditions[extra_platformuseconditions]']" element
    # Two clicks, not one: "Validate" is a plain <a href="javascript:void">
    # that only runs client-side field validation and, once everything
    # passes, reveals a real "Register" <button> plus a confirmation notice
    # ("You confirm that you really want to subscribe to this platform.") —
    # confirmed live by screenshotting the DOM before/after clicking
    # "Validate" alone. Submitting is the SECOND click, on "Register".
    And I follow "Validate"
    And I wait for the page content to settle
    Then I should see "You confirm that you really want to subscribe to this platform."
    And I press "Register"
    And I wait for the page to be loaded
    Then I should see "Your personal settings have been registered"
    And I should not see an error

    # ---- DIAGNOSIS FORM ----
    Given I am on "/main/search/search.php"
    And I wait for the page to be loaded
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

    # Sector
    And I click element "div.display-panel-collapse__header" containing text "I would like to choose a sector"
    And I wait for the page content to settle
    And I click the "input[name='extra_filiere_user[extra_filiere_user]'][value='art-et-culture']" element
    And I click the "[id='user_form_submit_partial[filiere]']" element
    And I wait for the page content to settle
    Then I should not see an error

    # Domains and themes (priority-ranked selects)
    And I click element "div.display-panel-collapse__header" containing text "The topics that interest me"
    And I wait for the page content to settle
    And I select "vie-quotidienne" from "extra_domaine_0"
    And I select "arrivee-sur-mon-poste-de-travail" from "extra_domaine_1"
    And I select "competente-dans-mon-domaine-de-specialite" from "extra_domaine_2"
    And I select "theme1" from "extra_theme_fr_0"
    And I click the "[id='user_form_submit_partial[theme_obj]']" element
    And I wait for the page content to settle
    Then I should not see an error

    # Language level: second option for each skill
    And I click element "div.display-panel-collapse__header" containing text "My language level"
    And I wait for the page content to settle
    And I select "JePeuxComprendreLessentielDannoncesEtDeMessagesSimplesEtClairs" from "extra_ecouter"
    And I select "JePeuxComprendreDesTextesCourtsTresSimplesEtTrouverUneInformationParticuliere" from "extra_lire"
    And I select "JePeuxAvoirDesEchangesTresBrefsMemeSiEnGeneralJeNeComprendsPasAssezPourPoursuivreUneConversation" from "extra_participer_a_une_conversation"
    And I select "JePeuxUtiliserUneSerieDePhrasesOuDexpressionsPourDecrireSimplementMonEntourage" from "extra_s_exprimer_oralement_en_continu"
    And I select "JePeuxEcrireUneLettrePersonnelleTresSimplePExDeRemerciements" from "extra_ecrire"
    And I click the "[id='user_form_submit_partial[niveau_langue]']" element
    And I wait for the page content to settle
    Then I should not see an error

    # Send the whole diagnostic form
    And I click the "#user_form_submit" element
    And I wait for the page to be loaded
    Then I should not see an error

    # ---- FIRST NAVIGATION ----
    When I am on "/sessions"
    And I wait for the page to be loaded
    Then I should see "My sessions"
    And I should not see an error

    Given I am on "/social"
    And I wait for the page to be loaded
    Then I should not see an error

    Given I am on "/resources/messages"
    And I wait for the page to be loaded
    Then I should not see an error


  # Back to the state specialCase1PlatformSettings' teardown leaves, which the
  # remaining special-case files were written against.
  Scenario: Restore the registration settings
    Given I am a platform administrator
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=required_extra_fields_in_inscription"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_required_extra_fields_in_inscription |  |
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_fields_inscription"
    And I wait for the page to be loaded
    And I fill in the following:
      | form_allow_fields_inscription |  |
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_terms_conditions"
    And I wait for the page to be loaded
    And I select "Yes" from "form_allow_terms_conditions"
    And I press "Save settings"
    And I wait for the page to be loaded

    Given I am on "/admin/settings/search_settings?keyword=allow_registration"
    And I wait for the page to be loaded
    And I select "Approval" from "form_allow_registration"
    And I press "Save settings"
    And I wait for the page to be loaded
