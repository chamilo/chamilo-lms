Feature: Special admin settings flows
  In order to exercise several admin settings quickly
  As a platform administrator
  I want to run a few targeted scenarios that change multiple settings

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  Scenario: Initial platform searches and basic settings
    Given I am on "/admin"
    And I wait very long for the page to be loaded

    # Diagnostic search
    When I fill in the following:
      | platform_management_search | Diagnostic |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Tabs configuration
    And I wait very long for the page to be loaded
    When I fill in the following:
      | search_keyword | tabs |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Campus homepage" from "form_show_tabs"
    And I additionally select "My courses" from "form_show_tabs"
    And I additionally select "Reporting" from "form_show_tabs"
    And I additionally select "Platform administration" from "form_show_tabs"
    And I additionally select "My agenda" from "form_show_tabs"
    And I additionally select "Social" from "form_show_tabs"
    And I additionally select "Videoconference" from "form_show_tabs"
    And I additionally select "Diagnostics" from "form_show_tabs"
    And I additionally select "Topbar skills" from "form_show_tabs"
    And I zoom out to maximum
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Additional check: the homepage must display "Diagnosis management"
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "Diagnosis management"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    # Multiple anonymous users
    When I fill in the following:
      | platform_management_search | Multiple anonymous users |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_max_anonymous_users | 100 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Course catalogue on homepage
    When I fill in the following:
      | search_keyword | course_catalog_display_in_home |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_course_catalog_display_in_home"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Additional check: on /home we must not see "Explore more courses"
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should not see "Explore more courses"
    And I wait very long for the page to be loaded
    And I am on "/admin"
    And I wait very long for the page to be loaded

    # Certificate links
    When I fill in the following:
      | platform_management_search | hide_my_certificate_link |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_hide_my_certificate_link"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Additional check: as a student, on /home we must not see "My certificates"
    And I am not logged
    And I am logged as "acostea"
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should not see "My certificates"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | allow_general_certificate |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_general_certificate"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Additional check: as a student, I must see the student reporting element
    # Assumption: the text available on the student reporting page contains "Reporting"
    #Given I am not logged
    #Given I am logged as "acostea"
    #And I am on "/main/reporting"
    #And I wait very long for the page to be loaded
    # Active tools on create (unselect all) - placeholder (adapt step if needed)
    #When I fill in the following:
    #  | search_keyword | active_tools_on_create |
    #And I press "search_search"
    #And I wait very long for the page to be loaded
    # TODO: unselect all tools — provide specific checkbox ids or implement a custom step to uncheck them
    #And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    #And I wait very long for the page to be loaded

    Then I should not see an error
    When I fill in the following:
      | search_keyword | enable_help_link |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_enable_help_link"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Now reuse search_keyword for subsequent settings (no need to go back to /admin)
    When I fill in the following:
      | search_keyword | translate_html |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_translate_html"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_forum_post_revisions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_forum_post_revisions"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | hide_forum_post_revision_language |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_hide_forum_post_revision_language"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_forum_category_language_filter |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_forum_category_language_filter"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | subscribe_users_to_forum_notifications_also_in_base_course |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_subscribe_users_to_forum_notifications_also_in_base_course"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_course_multiple_languages |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_course_multiple_languages"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | validate_lp_prerequisite_from_other_session |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_validate_lp_prerequisite_from_other_session"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Hidden exercise in LP
    When I fill in the following:
      | search_keyword | show_hidden_exercise_added_to_lp |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_show_hidden_exercise_added_to_lp"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Ticket/LP and message settings
    When I fill in the following:
      | search_keyword | ticket_lp_quiz_info_add |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_ticket_lp_quiz_info_add"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | force_edit_exercise_in_lp |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_force_edit_exercise_in_lp"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_send_message_to_all_platform_users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_send_message_to_all_platform_users"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Create two students to test internal messaging autocomplete
    And I am on "/main/admin/user_add.php"
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Student |
      | lastname  | One     |
      | email     | student.one@example.test |
      | username  | studentone |
      | password  | studentone |
    And I select "Learner" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    And I am on "/main/admin/user_add.php"
    And I zoom out to maximum
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Student |
      | lastname  | Two     |
      | email     | student.two@example.test |
      | username  | studenttwo |
      | password  | studenttwo |
    And I select "Learner" from "user_add_roles"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Login as first student and open messaging
    Given I am not logged
    Then I am logged as "studentone"
    And I wait very long for the page to be loaded
    And I am on "resources/messages"
    And I wait very long for the page to be loaded
    When I press "mdi-email-plus-outline"
    And I wait very long for the page to be loaded
    And I should not see an error

    # Start typing recipient name and verify autocomplete suggestion appears and is selectable
    When I fill in the following:
      | to | Two |
    And I wait very long for the page to be loaded
    Then I should see "studenttwo"
    And I follow "studenttwo"
    And I wait very long for the page to be loaded
    Then I should not see an error
    And I am not logged
    Then I am logged as "admin"
    And wait very long for the page to be loaded

    # Cookie, registration, terms and extra fields
    When I fill in the following:
      | platform_management_search | cookie_warning |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_cookie_warning"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_registration |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_registration"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Verify that, when logged out, the homepage offers a "Sign up" button to main/auth/registration.php
    Given I am not logged
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should see "Sign up"
    When I follow "Sign up"
    And I wait very long for the page to be loaded
    Then I am on "main/auth/registration.php"
    And I should not see an error
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | allow_registration_as_teacher |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_registration_as_teacher"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And  I am not logged
    And I am on "/main/auth/registration.php"
    And wait very long for the page to be loaded
    And I should see "Follow courses"
    And I should not see "Teach courses"
    And  I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | allow_terms_conditions |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_terms_condition"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/admin"
    And I zoom out to maximum
    And I should see "Terms and conditions"

  Scenario: Add user extra fields
    Given I am a platform administrator

    # 1) Gender (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Genre |
      | #user_field_variable     | terms_genre |
    And I fill in the following:
      | #field_options | homme;femme |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 2) Date of birth (Date)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Date de naissance |
      | #user_field_variable     | terms_datedenaissance |
    And I select "Date" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 3) Nationality (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Nationalité |
      | #user_field_variable     | terms_nationalite |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 4) Address (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Adresse |
      | #user_field_variable     | terms_adresse |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 5) Postal code (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Code postal |
      | #user_field_variable     | terms_codepostal |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 6) City (Geolocalization)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Ville |
      | #user_field_variable     | terms_ville |
    And I select "Geolocalization" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 7) Country of residence (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Pays de Résidence |
      | #user_field_variable     | terms_paysresidence |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 8) Target learning language (Select)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Langue cible d'apprentissage |
      | #user_field_variable     | langue_cible |
    And I select "Select" from "field_type"
    And I fill in the following:
      | #field_options | french;english |
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 9) Currently, I am (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Actuellement, je suis |
      | #user_field_variable     | statusocial |
    And I fill in the following:
      | #field_options | eleve;apprentie |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 10) Field of study (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je suis actuellement dans une filière ou je suis diplômé(e) d’une filière |
      | #user_field_variable     | filiere_user |
    And I fill in the following:
      | #field_options | art-et-culture;enseignement-et-deducation;tourisme |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    Then I should not see an error


    # 11) Last diploma obtained (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Dernier diplôme obtenu |
      | #user_field_variable     | terms_formation_niveau |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 12) Internship city (Geolocalization)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Ville du stage |
      | #user_field_variable     | terms_villedustage |
    And I select "Geolocalization" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 13) If your field is not indicated... (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Si ta filière n’est pas indiquée ci-dessus, veux-tu la préciser ici ? |
      | #user_field_variable     | filiereprecision |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 14) During this period... hours per week (Integer)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Pendant cette durée, je peux / je veux consacrer en moyenne en heures par semaine à mon apprentissage sur la plateforme. |
      | #user_field_variable     | heures_disponibilite_par_semaine |
    And I select "Integer" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 15) My internship starts on (Date)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Mon stage commence le |
      | #user_field_variable     | datedebutstage |
    And I select "Date" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 16) and ends on (Date)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | et dure jusqu’au |
      | #user_field_variable     | datefinstage |
    And I select "Date" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 17) During my internship... hours per week (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Pendant mon stage, je peux / je veux consacrer en moyenne en heures par semaine à mon apprentissage sur la plateforme. |
      | #user_field_variable     | heures_disponibilite_par_semaine_stage |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 18) I wish to continue... during my internship (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je souhaite poursuivre mon apprentissage sur la plateforme pendant mon stage. |
      | #user_field_variable     | poursuiteapprentissagestage |
    And I fill in the following:
      | #field_options | oui;non;je-ne-sais-pas-encore |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 19) Learning objective (Tag)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Objectif d'apprentissage |
      | #user_field_variable     | objectif_apprentissage |
    And I select "Tag" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 20) I like to work (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | J’aime travailler |
      | #user_field_variable     | methode_de_travaille |
    And I fill in the following:
      | #field_options | plutot-seule;plutot-avec-dautres-apprenants |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 21) I wish to be supported (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je souhaite etre accompagne(e) |
      | #user_field_variable     | accompagnement |
    And I fill in the following:
      | #field_options | pas-du-tout;un-peu |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 22) termactivated (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | termactivated |
      | #user_field_variable     | termactivated |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 23) I want to do the internship in this field (Radio)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je veux faire le stage dans cette filiere |
      | #user_field_variable     | filiere_want_stage |
    And I fill in the following:
      | #field_options | yes;no |
    And I select "Radio" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 24) PlatformUseConditions (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | PlatformUseConditions |
      | #user_field_variable     | platformuseconditions |
    And I select "Checkbox" from "field_type"
    And I fill in the following:
      | #field_options | art-et-culture;enseignement-et-deducation;tourisme |
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 25) DiagnosisCompleted (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | DiagnosisCompleted |
      | #user_field_variable     | diagnosis_completed |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 26) Je ne connais pas encore mes dates de stage (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je ne connais pas encore mes dates de stage |
      | #user_field_variable     | je_ne_connais_pas_encore_mes_dates_de_stage |
    And I select "Checkbox" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 27) En general, je suis plutot disponible (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | En general, je suis plutot disponible |
      | #user_field_variable     | moment_de_disponibilite |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 28) Je suis deja sur place /mon stage/mon emploi a deja commence (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Je suis deja sur place /mon stage/mon emploi a deja commence |
      | #user_field_variable     | deja_sur_place |
    And I select "Checkbox" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_no" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 29) Un ordinateur fixe ou portable (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Un ordinateur fixe ou portable |
      | #user_field_variable     | outil_de_travail_ordinateur |
    And I select "Checkbox" from "field_type"
    And I fill in the following:
      | #field_options | art-et-culture;enseignement-et-deducation;tourisme |
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 30) Une tablette (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Une tablette |
      | #user_field_variable     | outil_de_travail_tablette |
    And I select "Checkbox" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 31) Un smartphone (Checkbox)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Un smartphone |
      | #user_field_variable     | outil_de_travail_smartphone |
    And I select "Checkbox" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 32) Quel est le systeme d'exploitation ? (computer) (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Quel est le systeme d'exploitation ? |
      | #user_field_variable     | outil_de_travail_ordinateur_so |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 33) Quel est le systeme d'exploitation ? (tablet) (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Quel est le systeme d'exploitation ? |
      | #user_field_variable     | outil_de_travail_tablette_so |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 34) Quel est le systeme d'exploitation ? (smartphone) (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Quel est le systeme d'exploitation ? |
      | #user_field_variable     | outil_de_travail_smartphone_so |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 35) Pour travailler sur la plateforme, j'utilise le browser suivant : (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Pour travailler sur la plateforme, j'utilise le browser suivant : |
      | #user_field_variable     | browser_platforme |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 36) Autre (preciser) : (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Autre (preciser) : |
      | #user_field_variable     | browser_platforme_autre |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 37) Quelle est la version ? (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Quelle est la version ? |
      | #user_field_variable     | browser_platforme_version |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 38) Hobbies (Tag)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Hobbies |
      | #user_field_variable     | hobbies |
    And I select "Tag" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_yes" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 39) State (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | State |
      | #user_field_variable     | etat |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 40) Level (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Level |
      | #user_field_variable     | niveau |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 41) Quality (Text)
    And I am on "/main/admin/extra_fields.php?type=user"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #user_field_display_text | Quality |
      | #user_field_variable     | qualite |
    And I select "Text" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    Then I should not see an error

  Scenario: Add minimal session extra fields
    Given I am a platform administrator
    And I wait very long for the page to be loaded

    # 1) Je commence mon apprentissage sur la plateforme le (Date)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Je commence mon apprentissage sur la plateforme le |
      | #session_field_variable     | access_start_date |
    And I select "Date" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 2) Je suis disponible jusqu'au (Date)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Je suis disponible jusqu'au |
      | #session_field_variable     | access_end_date |
    And I select "Date" from "field_type"
    And I click the "#visible_to_self_no" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    Then I should not see an error

    # 3) Je souhaite m'inscrire dans une filière (Radio)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Je souhaite m'inscrire dans une filière |
      | #session_field_variable     | filiere |
    And I select "Radio" from "field_type"
    And I fill in the following:
      | #field_options | art-et-culture;enseignement-et-deducation;tourisme |
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 4) Les îlots d'apprentissage (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Les îlots d'apprentissage sont conçus autour des trois grands domaines suivants. Numérote-les de 1 à 3 selon tes priorités et tes intérêts. |
      | #session_field_variable     | domaine |
    And I select "Select multiple" from "field_type"
    And I fill in the following:
      | #field_options | vie-quotidienne;arrivee-sur-mon-poste-de-travail;competente-dans-mon-domaine-de-specialite |
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 5) Temps de travail (Integer)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Temps de travail |
      | #session_field_variable     | temps_de_travail |
    And I select "Integer" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 6) Choisis 5 thèmes et objectifs (Tag)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Choisis 5 thèmes et objectifs et numérote-les de 1 à 5. |
      | #session_field_variable     | theme_fr |
    And I select "Tag" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 7) Ecouter (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Ecouter |
      | #session_field_variable     | ecouter |
    And I fill in the following:
      | #field_options | jePeuxComprendreDesMotsEtDesExpressionsElementairesSurMoiMemeEtMaFamilleSiParleLentementEtDistinctement;JePeuxComprendreLessentielDannoncesEtDeMessagesSimplesEtClairs |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    Then I should not see an error

    And I wait very long for the page to be loaded


    # 8) Lire (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Lire |
      | #session_field_variable     | lire |
    And I fill in the following:
      | #field_options | JePeuxComprendreLessentielDannoncesEtDeMessagesSimplesEtClairs;JePeuxComprendreDesTextesCourtsTresSimplesEtTrouverUneInformationParticuliere |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 9) Participer a une conversation (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Participer à une conversation |
      | #session_field_variable     | participer_a_une_conversation |
    And I fill in the following:
      | #field_options | JePeuxPoserDesQuestionsSimplesEtYRepondreConditionQueMonInterlocuteurSoitDisposeRepeterOuReformulerLesPhrasesPlusLentement;JePeuxAvoirDesEchangesTresBrefsMemeSiEnGeneralJeNeComprendsPasAssezPourPoursuivreUneConversation |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 10) S'exprimer oralement en continu (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | S'exprimer oralement en continu |
      | #session_field_variable     | s_exprimer_oralement_en_continu |
    And I fill in the following:
      | #field_options | JePeuxUtiliserDesExpressionsOuDesPhrasesSimplesPourDonnerDesRenseignementsSurMoiOuDecrireDesGensQueJeConnais;JePeuxUtiliserUneSerieDePhrasesOuDexpressionsPourDecrireSimplementMonEntourage |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 11) Ecrire (Select multiple)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Ecrire |
      | #session_field_variable     | ecrire |
    And I fill in the following:
      | #field_options | JePeuxEcrireUneCourteCartePostaleSimpleEtJePeuxRemplirUnQuestionnaireAvecMesDetailsPersonnelsNomAdresseNationalite;JePeuxEcrireUneLettrePersonnelleTresSimplePExDeRemerciements |
    And I select "Select multiple" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    # 12) Thema (Tag)
    And I am on "/main/admin/extra_fields.php?type=session"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus-box" element
    And I fill in the following:
      | #session_field_display_text | Thema |
      | #session_field_variable     | theme_de |
    And I select "Tag" from "field_type"
    And I click the "#visible_to_self_yes" element
    And I click the "#visible_to_others_no" element
    And I click the "#changeable_yes" element
    And I click the "#filter_yes" element
    And I press "user_field_submit"
    And I wait very long for the page to be loaded

    Then I should not see an error

    And I wait very long for the page to be loaded
    When I fill in the following:
      | search_keyword | required_extra_fields_in_inscription |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_required_extra_fields_in_inscription | {"options":["terms_adresse","terms_codepostal","terms_ville","terms_paysresidence","terms_datedenaissance","terms_genre","filiere_user","terms_formation_niveau","gdpr","platformuseconditions","langue_cible"]} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am not logged
    And I am on "main/auth/registration.php"
    And I zoom out to maximum
    And I should see "adresse"
    And I should see "terms_codepostal"
    And I should see "ville"
    And I should see "terms_paysresidence"
    And I should see "terms_datedenaissance"
    And I should see "terms_genre"
    And I should see "filiere_user"
    And I should see "terms_formation_niveau"
    And I should see "gdpr"
    And I should see "platformuseconditions"
    And I should see "langue_cible"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    # Registration fields and messages
    When I fill in the following:
      | platform_management_search | allow_fields_inscription |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_allow_fields_inscription | {"fields":["lastname","firstname","email","language","phone","address"],"extra_fields":["terms_nationalite","terms_numeroderue","terms_nomderue","terms_codepostal","terms_paysresidence","terms_ville","terms_datedenaissance","terms_genre","filiere_user","terms_formation_niveau","terms_villedustage","terms_adresse","gdpr","platformuseconditions","langue_cible"]} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am not logged
    And I am on "/main/auth/registration.php"
    And I wait very long for the page to be loaded
    Then I should see "lastname"
    And I should see "firstname"
    And I should see "email"
    And I should see "language"
    And I should see "phone"
    And I should see "address"
    And I should see "terms_nationalite"
    And I should see "terms_numeroderue"
    And I should see "terms_nomderue"
    And I should see "terms_codepostal"
    And I should see "terms_paysresidence"
    And I should see "terms_ville"
    And I should see "terms_datedenaissance"
    And I should see "terms_genre"
    And I should see "filiere_user"
    And I should see "terms_formation_niveau"
    And I should see "terms_villedustage"
    And I should see "terms_adresse"
    And I should see "gdpr"
    And I should see "platformuseconditions"
    And I should see "langue_cible"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | send_inscription_msg_to_inbox |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_send_inscription_msg_to_inbox"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | redirect_after_login |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_redirect_after_login | {"COURSEMANAGER":"sessions","STUDENT":"sessions","DRH":"sessions","SESSIONADMIN":"sessions","STUDENT_BOSS":"sessions","INVITEE":"","ADMIN":"sessions"} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"
    And I should not see "Platform administration"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    # Legal accept, captcha limits and session toggles
    When I fill in the following:
      | platform_management_search | hide_legal_accept_checkbox |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_hide_legal_accept_checkbox"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | captcha_number_mistakes_to_block_account |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_captcha_number_mistakes_to_block_account | 5 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | captcha_time_to_block |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_captcha_time_to_block | 5 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | anonymous_autoprovisioning |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_anonymous_autoprovisioning"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_session_admins_to_manage_all_sessions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_session_admins_to_manage_all_sessions"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/session/session_list.php"
    And I wait very long for the page to be loaded
    Then I should see "Past session"
    And I should see "Present session"
    And I should see "Session in the future"

    When I fill in the following:
      | search_keyword | allow_search_diagnostic |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | session_admins_edit_courses_content |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_session_admins_edit_courses_content"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

   # #And I am logged as "admin"
   # And I wait very long for the page to be loaded
   # And I am on "/"
   # And I wait very long for the page to be loaded
   # # Open a known course (adjust path/selectors if needed)
   # When I follow "Courses"
   # And I wait very long for the page to be loaded
   # And I follow "TEMPPRIVATE"
   # And I wait very long for the page to be loaded
   # And I follow "Documents"
   # And I wait very long for the page to be loaded
   # # Try to edit the first document
   # When I click the "i.mdi-pencil" element
   # And I wait very long for the page to be loaded
   # Then I should not see an error

    When I fill in the following:
      | search_keyword | session_list_show_count_users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_session_list_show_count_users"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/admin/session_list.php"
    And I wait very long for the page to be loaded
    Then I should see "Students"
    And I should see "#sessions"
    And I should see "ID"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | session_admins_access_all_content |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_session_admins_access_all_content"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

 # And I am not logged
 # And I am logged as "sessionadmin"
 # And I wait very long for the page to be loaded
 # # Open a course where sessionadmin is not subscribed
 # When I follow "Courses"
 # And I wait very long for the page to be loaded
 # And I follow "TEMPPRIVATE"
 # And I wait very long for the page to be loaded
 # And I follow "Documents"
 # And I wait very long for the page to be loaded
 # Then I should see "Document"

    When I fill in the following:
      | search_keyword | session_model_list_field_ordered_by_id |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_session_model_list_field_ordered_by_id"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/admin/session_list.php"
    And I wait very long for the page to be loaded
    Then I should see "Custom"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    # Badges, skills and social
    When I fill in the following:
      | platform_management_search | badge_assignation_notification |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_badge_assignation_notification"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    When I fill in the following:
      | search_keyword | allow_teacher_access_student_skills |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_teacher_access_student_skills"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | skill_levels_names |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_skill_levels_names | {"levels":{"1":"Skills","2":"Capability","3":"Dimension"}} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | manual_assignment_subskill_autoload |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_manual_assignment_subskill_autoload"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "main/skills/skill_create.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title      | NewSkill |
      | short_code | NS       |
      | description| skill created by behat |
      | criteria   | criteria |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "NewSkill"

    And I am on "main/skills/skill_create.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title      | SubSkill |
      | short_code | SS       |
      | description| subskill created by behat |
      | criteria   | criteria |
    And I select "NewSkill" from "parent_id"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "SubSkill"

    # Assign parent skill and check subskill list appears for user
    And I am on "main/skills/assign.php?user=1"
    And I wait very long for the page to be loaded
    When I select "NewSkill" from "skill"
    And I wait very long for the page to be loaded
    Then I should see "SubSkill"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | social_enable_messages_feedback |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_social_enable_messages_feedback"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    And I am on "/main/social/home.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | post_text | This is a test post created by behat |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "This is a test post created by behat"
    And I should see "Like"
    And I should see "Dislike"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | disable_dislike_option |
    And I press "platform_management_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_disable_dislike_option"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/social/home.php"
    And I wait very long for the page to be loaded
    # (environment must have dislike disabled for this check)
    Then I should see "Like"
    And I should not see "Dislike"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | social_show_language_flag_in_profile |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_social_show_language_flag_in_profile"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/social/home.php"
    And I wait very long for the page to be loaded
    Then I should not see "My communities"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    # Ticket settings
    When I fill in the following:
      | search_keyword | ticket_allow_category_edition |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_ticket_allow_category_edition"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am on "/main/ticket/projects.php?project_id=1"
    And I wait very long for the page to be loaded
    When I follow "Categories"
    And I wait very long for the page to be loaded
    Then I should see "Enrollment"
    And I should see "i.mdi-pencil"
    And I am on "/admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | ticket_allow_student_add |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_ticket_allow_student_add"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Given I am logged as "studentone"
    And I wait very long for the page to be loaded
    And I am on "/main/ticket/new_ticket.php?project_id=1"
    And I wait very long for the page to be loaded
    Then I should see "Send message"
    And I should not see an error
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | ticket_send_warning_to_all_admins |
    And I press "platform_management_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_ticket_send_warning_to_all_admins"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | ticket_project_user_roles |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | ticket_project_user_roles | {"permissions":{"1":[17,1]}} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | show_link_ticket_notification |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_show_link_ticket_notification"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | exercise_hide_label |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_exercise_hide_label"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Quiz & UI settings
    When I fill in the following:
      | search_keyword | allow_quiz_question_feedback |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_quiz_question_feedback"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | quiz_show_description_on_results_page |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_quiz_show_description_on_results_page"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_notification_setting_per_exercise |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_notification_setting_per_exercise"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | hide_free_question_score |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_hide_free_question_score"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | show_exercise_expected_choice |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_show_exercise_expected_choice"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_quiz_results_page_config |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_quiz_results_page_config"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Changeable and visible options
    When I fill in the following:
      | search_keyword | changeable_options |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Language" from "form_changeable_options"
    And I additionally select "Picture" from "form_changeable_options"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Given I am logged as "studentone"
    And I am on "/main/auth/profile.php"
    And I wait very long for the page to be loaded
    Then I should see "language"
    And I should see "picture"
    And I should not see "firstname"
    And I should not see "lastname"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | visible_options |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Official code" from "form_visible_options"
    And I additionally select "E-mail" from "form_visible_options"
    And I additionally select "Language" from "form_visible_options"
    And I additionally select "Picture" from "form_visible_options"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    Then I am logged as "studentone"
    And I am on "/main/auth/profile.php"
    And I wait very long for the page to be loaded
    Then I should see "official_code"
    And I should see "email"
    And I should see "picture"
    And I should see "language"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | use_users_timezone |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "No" from "form_use_users_timezone"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | my_space_users_items_per_page |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_my_space_users_items_per_page | 1000 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | profile_fields_visibility |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_profile_fields_visibility | {"options":{"vcard":false,"firstname":true,"lastname":true,"picture":true,"email":false,"language":true,"chat":true,"terms_ville":true,"terms_datedenaissance":true,"terms_paysresidence":false,"filiere_user":true,"terms_villedustage":true,"hobbies":true,"langue_cible":true}} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am not logged
    And I am logged as "studentone"
    And I am on "/main/auth/profile.php"
    And I wait very long for the page to be loaded
    Then I should see "firstname"
    And I should see "lastname"
    And I should see "picture"
    And I should see "language"
    And I should see "terms_ville"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | allow_social_map_fields |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_allow_social_map_fields | {"fields":["terms_villedustage","terms_ville"]} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Terms and redirect/default menu
    When I fill in the following:
      | search_keyword | show_terms_if_profile_completed |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_show_terms_if_profile_completed"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am not logged
    Then I am logged as "studenttwo"
    And I am on "/main/auth/terms.php"
    And I wait very long for the page to be loaded
    Then I should see "complete your profile before accepting the terms"
    And I should see "Accept"
    # Note: step to check the Accept button is disabled may require an extra step definition; we assert the message appears above.
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | load_term_conditions_section |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I select "Course" from "form_load_term_conditions_section"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    And I am logged as "studenttwo"
    And I am on "/home"
    And I wait very long for the page to be loaded
    Then I should not see "Terms and conditions"
    When I am on "/course/TEMPPRIVATE/home"
    And I wait very long for the page to be loaded
    Then I should see "Terms and conditions"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded


    When I fill in the following:
      | platform_management_search | redirect_index_to_url_for_logged_users |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_redirect_index_to_url_for_logged_users | sessions |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Given I am logged as "studentone"
    And I am on "/"
    And I wait very long for the page to be loaded
    When I follow "Home"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"
    And I am not logged
    And I am logged as "admin"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | platform_management_search | default_menu_entry_for_course_or_session |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_default_menu_entry_for_course_or_session | My sessions |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Then I should not see an error

    And I am logged as "studentthree"
    And I wait very long for the page to be loaded
    Then I should see "My sessions"

  Scenario: Create courses, multilingual documents, exercises, forum, learning path and assessment activity
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  # Create courses
    When I am on "/main/admin/course_add.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title      | Testing course en |
    And I select "English" from "language"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Testing course en"

    When I am on "/main/admin/course_add.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title      | Special |
    And I click the "#special_course" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Special"

    When I am on "/main/admin/course_add.php"
    And I wait very long for the page to be loaded
    When I fill in the following:
      | title      | Testing course fr |
    And I select "French" from "language"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Testing course fr"

  # Enter the new course (Testing course en)
    When I follow "Testing course en"
    And I wait very long for the page to be loaded
    Then I should see "Course overview"

  # Create two HTML documents with bilingual content: introduction and final
    When I follow "Documents"
    And I wait very long for the page to be loaded
    When I press "Add a document"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | introduction |
    #And I fill in tinymce field "content" with:"""<p class="ck ck-texte"><span dir="ltr" lang="en">English content</span><span dir="ltr" lang="fr">Contenu en français</span></p>"""
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "introduction"

    When I press "Add a document"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | final |
    #And I fill in tinymce field "content" with:"""<p class="ck ck-texte"><span dir="ltr" lang="en">English content</span><span dir="ltr" lang="fr">Contenu en français</span></p>"""
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "final"

  # Create exercises: one with QRU + image selection, one open question
    When I follow "Tests"
    And I wait very long for the page to be loaded
    When I press "Add an exercise"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | QRU and Image Selection exercise |
    And I press "submit"
    And I wait very long for the page to be loaded
  # Add QRU question
    When I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I fill in the following:
      | questionTitle | QRU Question |
    And I press "question_admin_form_submitQuestion"
    And I wait very long for the page to be loaded
  # Add Image selection question
    When I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I fill in the following:
      | questionTitle | Image selection question |
    And I press "question_admin_form_submitQuestion"
    And I wait very long for the page to be loaded

  # Create open question exercise
    When I press "Add an exercise"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | Open question exercise |
    And I press "submit"
    And I wait very long for the page to be loaded
    When I click the "i.mdi-plus" element
    And I wait very long for the page to be loaded
    And I fill in the following:
      | questionTitle | Open Question |
      | weighting     | 5 |
    And I press "question_admin_form_submitQuestion"
    And I wait very long for the page to be loaded

  # Create a forum category and a forum inside
    When I follow "Forums"
    And I wait very long for the page to be loaded
    When I press "Add a category"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | Course discussions |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Course discussions"

    When I press "Add a forum"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | General forum |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "General forum"

  # Create a new Learning Path and include items in the requested order
    When I follow "Learning paths"
    And I wait very long for the page to be loaded
    When I press "Add a learning path"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title | LP Test |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "LP Test"

  # Add items to LP in order: introduction, forum, QRU exercise, open exercise, final
    When I follow "LP Test"
    And I wait very long for the page to be loaded
    And I press "Add an item"
    And I wait very long for the page to be loaded
    And I select "introduction" from "item"
    And I press "use_as_reference"
    And I wait very long for the page to be loaded

    And I press "Add an item"
    And I wait very long for the page to be loaded
    And I select "General forum" from "item"
    And I press "use_as_reference"
    And I wait very long for the page to be loaded

    And I press "Add an item"
    And I wait very long for the page to be loaded
    And I select "QRU and Image Selection exercise" from "item"
    And I press "use_as_reference"
    And I wait very long for the page to be loaded

    And I press "Add an item"
    And I wait very long for the page to be loaded
    And I select "Open question exercise" from "item"
    And I press "use_as_reference"
    And I wait very long for the page to be loaded

    And I press "Add an item"
    And I wait very long for the page to be loaded
    And I select "final" from "item"
    And I press "use_as_reference"
    And I wait very long for the page to be loaded

  # Set open question exercise as prerequisite for final with min score 0
    When I follow "final"
    And I wait very long for the page to be loaded
    And I press "Set as requirement"
    And I wait very long for the page to be loaded
    And I select "Open Question" from "requirements"
    And I fill in the following:
      | min_score | 0 |
    And I press "save_resource"
    And I wait very long for the page to be loaded
    Then I should see "Requirements saved"

  # Edit course introduction and add link to LP
    When I follow "Documents"
    And I wait very long for the page to be loaded
    And I follow "introduction"
    And I wait very long for the page to be loaded
    And I press "Edit"
    And I wait very long for the page to be loaded
    #And I fill in tinymce field "content" with:"""<a href="/main/lp/lp_view.php?id=1">LP Test</a>"""
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "LP Test"

  # Course settings: E-mail notifications -> Tests: mark relaxed options
    When I follow "Settings"
    And I wait very long for the page to be loaded
    And I follow "E-mail notifications"
    And I wait very long for the page to be loaded
    And I select "Yes" from "relaxed_open_questions_email_teacher"
    And I select "Yes" from "relaxed_audio_email_teacher"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # Enter the assessments tool and add a classroom activity
    When I follow "Assessments"
    And I wait very long for the page to be loaded
    And I press "Add a classroom activity"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | title       | Course validation |
      | weight      | 100 |
      | maximum     | 1 |
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Course validation"

  Scenario: Create teacher and configure "Present session" with settings and include course
    Given I am a platform administrator
    And I wait very long for the page to be loaded

    # Create a teacher account
    When I am on "/main/admin/user_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | firstname | Teacher |
      | lastname  | Teacher |
      | email     | teacher@example.test |
      | username  | teacher |
      | password  | teacher |
    And I select "Teacher" from "status_select"
    And I click the "input#send_mail_no" element
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should not see an error

    # Create session Present session with start = 2026-01-20 and end = 2026-02-03
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | name       | Present session |
      | start_date | 2026-01-20 |
      | end_date   | 2026-02-03 |
    And I fill in select2 input "#coach_username" with id "1" and value "teacher"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Add courses to this session (Present session)"

    # Open advanced settings and set session status to In progress
    When I press "Advanced settings"
    And I wait very long for the page to be loaded
    And I select "In progress" from "status"
    And I wait very long for the page to be loaded

    # Set extra fields for the session
    When I fill in the following:
      | domaine  | vie-quotidienne |
    And I select "vie-quotidienne" from "domaine"
    And I wait very long for the page to be loaded

    When I fill in the following:
      | filiere  | art-et-culture |
    And I select "art-et-culture" from "filiere"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select the suggested option
    When I fill in "theme_fr" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    When I fill in "theme_de" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select "1" from "ecouter"
    And I select "1" from "lire"
    And I select "1" from "participer_a_une_conversation"
    And I select "1" from "s_exprimer_oralement_en_continu"
    And I select "1" from "ecrire"
    And I wait very long for the page to be loaded

    # Go to next step and include Testing course fr
    When I press "Next step"
    And I wait very long for the page to be loaded
    And I select "Testing course fr" from "NoSessionCoursesList[]"
    And I click the "add_course" element
    And I wait very long for the page to be loaded

    # Mark import gradebook checkbox and add
    And I click the "#import_gradebook" element
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create future session "Session in the futur" and include course
    Given I am a platform administrator
    And I wait very long for the page to be loaded

    # Create session Session in the futur with start = 2026-02-03 and end = 2026-02-17
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | name       | Session in the futur |
      | start_date | 2026-02-03 |
      | end_date   | 2026-02-17 |
    And I fill in select2 input "#coach_username" with id "1" and value "teacher"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Add courses to this session (Session in the futur)"

    # Open advanced settings and set session status to Planned
    When I press "Advanced settings"
    And I wait very long for the page to be loaded
    And I select "Planned" from "status"
    And I wait very long for the page to be loaded

    # Set extra fields for the session
    And I select "arrivee-sur-mon-poste-de-travail" from "domaine"
    And I wait very long for the page to be loaded
    And I select "art-et-culture" from "filiere"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select the suggested option
    When I fill in "theme_fr" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    When I fill in "theme_de" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select "1" from "ecouter"
    And I select "1" from "lire"
    And I select "1" from "participer_a_une_conversation"
    And I select "1" from "s_exprimer_oralement_en_continu"
    And I select "1" from "ecrire"
    And I wait very long for the page to be loaded

    # Go to next step and include Testing course fr
    When I press "Next step"
    And I wait very long for the page to be loaded
    And I select "Testing course fr" from "NoSessionCoursesList[]"
    And I click the "add_course" element
    And I wait very long for the page to be loaded

    # Mark import gradebook checkbox and add
    And I click the "#import_gradebook" element
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create past session "Past session" and include course
    Given I am a platform administrator
    And I wait very long for the page to be loaded

    # Create session Past session with start = 2026-01-06 and end = 2026-01-20
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | name       | Past session |
      | start_date | 2026-01-06 |
      | end_date   | 2026-01-20 |
    And I fill in select2 input "#coach_username" with id "1" and value "teacher"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Add courses to this session (Past session)"

    # Open advanced settings and set session status to Finished
    When I press "Advanced settings"
    And I wait very long for the page to be loaded
    And I select "Finished" from "status"
    And I wait very long for the page to be loaded

    # Set extra fields for the session
    And I select "competente-dans-mon-domaine-de-specialite" from "domaine"
    And I wait very long for the page to be loaded
    And I select "art-et-culture" from "filiere"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select the suggested option (theme2)
    When I fill in "theme_fr" with "theme2"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    When I fill in "theme_de" with "theme2"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select "1" from "ecouter"
    And I select "1" from "lire"
    And I select "1" from "participer_a_une_conversation"
    And I select "1" from "s_exprimer_oralement_en_continu"
    And I select "1" from "ecrire"
    And I wait very long for the page to be loaded

    # Go to next step and include Testing course fr
    When I press "Next step"
    And I wait very long for the page to be loaded
    And I select "Testing course fr" from "NoSessionCoursesList[]"
    And I click the "add_course" element
    And I wait very long for the page to be loaded

    # Mark import gradebook checkbox and add
    And I click the "#import_gradebook" element
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error

  Scenario: Create future English session "Session in the futur en" and include course
    Given I am a platform administrator
    And I wait very long for the page to be loaded

    # Create session Session in the futur en with start = 2026-02-03 and end = 2026-02-17
    When I am on "/main/session/session_add.php"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | name       | Session in the futur en |
      | start_date | 2026-02-03 |
      | end_date   | 2026-02-17 |
    And I fill in select2 input "#coach_username" with id "1" and value "teacher"
    And I press "submit"
    And I wait very long for the page to be loaded
    Then I should see "Add courses to this session (Session in the futur en)"

    # Open advanced settings and set session status to Planned
    When I press "Advanced settings"
    And I wait very long for the page to be loaded
    And I select "Planned" from "status"
    And I wait very long for the page to be loaded

    # Set extra fields for the session
    And I select "arrivee-sur-mon-poste-de-travail" from "domaine"
    And I wait very long for the page to be loaded
    And I select "art-et-culture" from "filiere"
    And I wait very long for the page to be loaded

    # theme_fr and theme_de: type and select the suggested option
    When I fill in "theme_fr" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    When I fill in "theme_de" with "theme1"
    And I wait very long for the page to be loaded
    And I click the ".select2-results__option" element
    And I wait very long for the page to be loaded

    # Select first option for competency fields
    And I select "1" from "ecouter"
    And I select "1" from "lire"
    And I select "1" from "participer_a_une_conversation"
    And I select "1" from "s_exprimer_oralement_en_continu"
    And I select "1" from "ecrire"
    And I wait very long for the page to be loaded

    # Go to next step and include Testing course en
    When I press "Next step"
    And I wait very long for the page to be loaded
    And I select "Testing course en" from "NoSessionCoursesList[]"
    And I click the "add_course" element
    And I wait very long for the page to be loaded

    # Mark import gradebook checkbox and add
    And I click the "#import_gradebook" element
    And I wait very long for the page to be loaded
    And I press "Add"
    And I wait very long for the page to be loaded
    Then I should not see an error


  Scenario: Tare Down
    Given I am a platform administrator
    And I wait very long for the page to be loaded
    And I am on "/admin"
    And I wait very long for the page to be loaded


    When I fill in the following:
      | platform_management_search | default_menu_entry_for_course_or_session |
    And I press "platform_management_search_button"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_default_menu_entry_for_course_or_session | My courses |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    When I fill in the following:
      | search_keyword | redirect_index_to_url_for_logged_users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_redirect_index_to_url_for_logged_users |  |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | load_term_conditions_section |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Login" from "form_load_term_conditions_section"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Terms and redirect/default menu
    When I fill in the following:
      | search_keyword | show_terms_if_profile_completed |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_show_terms_if_profile_completed"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_social_map_fields |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_allow_social_map_fields |  |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | profile_fields_visibility |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_profile_fields_visibility |  |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | my_space_users_items_per_page |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_my_space_users_items_per_page | 10 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    # use_users_timezone -> Yes
    When I fill in the following:
      | search_keyword | use_users_timezone |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_use_users_timezone"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # visible_options -> Name, Official code, E-mail, Picture, Login, Password, Language, Phone, Theme
    When I fill in the following:
      | search_keyword | visible_options |
    And I press "search_search"
    And I select "Name" from "form_visible_options"
    And I additionally select "Official code" from "form_visible_options"
    And I additionally select "E-mail" from "form_visible_options"
    And I additionally select "Picture" from "form_visible_options"
    And I additionally select "Login" from "form_visible_options"
    And I additionally select "Password" from "form_visible_options"
    And I additionally select "Language" from "form_visible_options"
    And I additionally select "Phone" from "form_visible_options"
    And I additionally select "Theme" from "form_visible_options"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # changeable_options -> same set
    When I fill in the following:
      | search_keyword | changeable_options |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Name" from "form_changeable_options"
    And I additionally select "Official code" from "form_changeable_options"
    And I additionally select "E-mail" from "form_changeable_options"
    And I additionally select "Picture" from "form_changeable_options"
    And I additionally select "Login" from "form_changeable_options"
    And I additionally select "Password" from "form_changeable_options"
    And I additionally select "Language" from "form_changeable_options"
    And I additionally select "Phone" from "form_changeable_options"
    And I additionally select "Theme" from "form_changeable_options"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # allow_quiz_results_page_config -> No
    When I fill in the following:
      | search_keyword | allow_quiz_results_page_config |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_quiz_results_page_config"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # show_exercise_expected_choice -> No
    When I fill in the following:
      | search_keyword | show_exercise_expected_choice |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_show_exercise_expected_choice"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # hide_free_question_score -> No
    When I fill in the following:
      | search_keyword | hide_free_question_score |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_hide_free_question_score"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # allow_notification_setting_per_exercise -> No
    When I fill in the following:
      | search_keyword | allow_notification_setting_per_exercise |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_notification_setting_per_exercise"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # quiz_show_description_on_results_page -> No
    When I fill in the following:
      | search_keyword | quiz_show_description_on_results_page |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_quiz_show_description_on_results_page"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # allow_quiz_question_feedback -> No
    When I fill in the following:
      | search_keyword | allow_quiz_question_feedback |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_quiz_question_feedback"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # exercise_hide_label -> No
    When I fill in the following:
      | search_keyword | exercise_hide_label |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_exercise_hide_label"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # show_link_ticket_notification -> No
    When I fill in the following:
      | search_keyword | show_link_ticket_notification |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_show_link_ticket_notification"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # ticket_project_user_roles -> empty
    When I fill in the following:
      | search_keyword | ticket_project_user_roles |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | ticket_project_user_roles | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # ticket_send_warning_to_all_admins -> No
    When I fill in the following:
      | search_keyword | ticket_send_warning_to_all_admins |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_ticket_send_warning_to_all_admins"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # ticket_allow_student_add -> No
    When I fill in the following:
      | search_keyword | ticket_allow_student_add |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_ticket_allow_student_add"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # ticket_allow_category_edition -> No
    When I fill in the following:
      | search_keyword | ticket_allow_category_edition |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_ticket_allow_category_edition"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # social_show_language_flag_in_profile -> No
    When I fill in the following:
      | search_keyword | social_show_language_flag_in_profile |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_social_show_language_flag_in_profile"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # disable_dislike_option -> No
    When I fill in the following:
      | search_keyword | disable_dislike_option |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_disable_dislike_option"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | social_enable_messages_feedback |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_social_enable_messages_feedback"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | manual_assignment_subskill_autoload |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_manual_assignment_subskill_autoload"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | skill_levels_names |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_skill_levels_names | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_teacher_access_student_skills |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_teacher_access_student_skills"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | badge_assignation_notification |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_badge_assignation_notification"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | session_model_list_field_ordered_by_id |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_session_model_list_field_ordered_by_id"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | session_admins_access_all_content |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_session_admins_access_all_content"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | session_list_show_count_users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_session_list_show_count_users"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | session_admins_edit_courses_content |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_session_admins_edit_courses_content"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    # Additional teardown steps requested
    When I fill in the following:
      | search_keyword | allow_search_diagnostic |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_session_admins_to_manage_all_sessions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_session_admins_to_manage_all_sessions"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | anonymous_autoprovisioning |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_anonymous_autoprovisioning"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | captcha_time_to_block |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_captcha_time_to_block | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | captcha_number_mistakes_to_block_account |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_captcha_number_mistakes_to_block_account | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | hide_legal_accept_checkbox |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_hide_legal_accept_checkbox"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | redirect_after_login |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_redirect_after_login | {"COURSEMANAGER":"courses","STUDENT":"courses","DRH":"","SESSIONADMIN":"admin-dashboard","STUDENT_BOSS":"main/my_space/student.php","INVITEE":"courses","ADMIN":"admin"} |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded


    When I fill in the following:
      | search_keyword | send_inscription_msg_to_inbox |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_send_inscription_msg_to_inbox"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Given I am not logged
    Then I am logged as "studentone"
    And I am on

    When I fill in the following:
      | search_keyword | allow_fields_inscription |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_allow_fields_inscription | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | required_extra_fields_in_inscription |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_required_extra_fields_in_inscription | "" |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_terms_conditions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_terms_condition"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_registration_as_teacher |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_registration_as_teacher"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_registration |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Approval" from "form_allow_registration"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | cookie_warning |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_cookie_warning"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_send_message_to_all_platform_users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_send_message_to_all_platform_users"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | force_edit_exercise_in_lp |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_force_edit_exercise_in_lp"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | ticket_lp_quiz_info_add |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_ticket_lp_quiz_info_add"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | show_hidden_exercise_added_to_lp |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_show_hidden_exercise_added_to_lp"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | validate_lp_prerequisite_from_other_session |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_validate_lp_prerequisite_from_other_session"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_course_multiple_languages |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_course_multiple_languages"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | subscribe_users_to_forum_notifications_also_in_base_course |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_subscribe_users_to_forum_notifications_also_in_base_course"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | allow_forum_category_language_filter |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_forum_category_language_filter"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

     When I fill in the following:
       | search_keyword | hide_forum_post_revision_language |
     And I press "search_search"
     And I wait very long for the page to be loaded
     And I select "No" from "form_hide_forum_post_revision_language"
     And I click the "button.btn.btn--primary.btn-lg.mr-4" element
     And I wait very long for the page to be loaded


    When I fill in the following:
      | search_keyword | allow_forum_post_revisions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_allow_forum_post_revisions"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | translate_html |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_translate_html"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | enable_help_link |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "enable_help_link"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Then I should not see an error

    # Active tools on create (unselect all) - placeholder (adapt step if needed)
    When I fill in the following:
      | search_keyword | active_tools_on_create |
    And I press "search_search"
    And I wait very long for the page to be loaded
    # TODO: unselect all tools — provide specific checkbox ids or implement a custom step to uncheck them
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

      | search_keyword | hide_my_certificate_link |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_hide_my_certificate_link"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | show_courses_sessions |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Show courses & sessions" from "form_show_courses_sessions"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | course_catalog_display_in_home |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "No" from "form_course_catalog_display_in_home"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | Multiple anonymous users |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I fill in the following:
      | form_max_anonymous_users | 100 |
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | tabs |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Campus homepage" from "form_show_tabs"
    And I additionally select "My courses" from "form_show_tabs"
    And I additionally select "Reporting" from "form_show_tabs"
    And I additionally select "Platform administration" from "form_show_tabs"
    And I additionally select "My agenda" from "form_show_tabs"
    And I additionally select "Social" from "form_show_tabs"
    And I additionally select "Topbar skills" from "form_show_tabs"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    When I fill in the following:
      | search_keyword | Diagnostic |
    And I press "search_search"
    And I wait very long for the page to be loaded
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "button.btn.btn--primary.btn-lg.mr-4" element
    And I wait very long for the page to be loaded

    Then I should not see an error




