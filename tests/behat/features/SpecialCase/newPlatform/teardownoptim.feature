Feature: TEARDOWN OPTIM — reset platform settings to defaults
  In order to restore the platform to its initial state
  As a platform administrator
  I want to reset all settings changed during SpecialCase1

  Background:
    Given I am a platform administrator
    And I wait very long for the page to be loaded

  # ==============================================================
  # SCENARIO 1 — Navigation, profile, timezone, visible options
  # ==============================================================
  Scenario: Teardown — platform navigation and profile settings

    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear

    # default_menu_entry_for_course_or_session -> my_courses
    When I fill in the following:
      | platform_management_search | default_menu_entry_for_course_or_session |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_default_menu_entry_for_course_or_session']" to appear
    And I select "my_courses" from "form_default_menu_entry_for_course_or_session"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # redirect_index_to_url_for_logged_users -> empty
    When I fill in the following:
      | search_keyword | redirect_index_to_url_for_logged_users |
    And I press "search_search"
    And I wait for the element "[name='form_redirect_index_to_url_for_logged_users']" to appear
    And I fill in the following:
      | form_redirect_index_to_url_for_logged_users |  |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # load_term_conditions_section -> Login
    When I fill in the following:
      | search_keyword | load_term_conditions_section |
    And I press "search_search"
    And I wait for the element "[name='form_load_term_conditions_section']" to appear
    And I select "Login" from "form_load_term_conditions_section"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # show_terms_if_profile_completed -> No
    When I fill in the following:
      | search_keyword | show_terms_if_profile_completed |
    And I press "search_search"
    And I wait for the element "[name='form_show_terms_if_profile_completed']" to appear
    And I select "No" from "form_show_terms_if_profile_completed"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_social_map_fields -> empty
    When I fill in the following:
      | search_keyword | allow_social_map_fields |
    And I press "search_search"
    And I wait for the element "[name='form_allow_social_map_fields']" to appear
    And I fill in the following:
      | form_allow_social_map_fields |  |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # profile_fields_visibility -> empty
    When I fill in the following:
      | search_keyword | profile_fields_visibility |
    And I press "search_search"
    And I wait for the element "[name='form_profile_fields_visibility']" to appear
    And I fill in the following:
      | form_profile_fields_visibility |  |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # my_space_users_items_per_page -> 10
    When I fill in the following:
      | search_keyword | my_space_users_items_per_page |
    And I press "search_search"
    And I wait for the element "[name='form_my_space_users_items_per_page']" to appear
    And I fill in the following:
      | form_my_space_users_items_per_page | 10 |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # use_users_timezone -> Yes
    When I fill in the following:
      | search_keyword | use_users_timezone |
    And I press "search_search"
    And I wait for the element "[name='form_use_users_timezone']" to appear
    And I select "Yes" from "form_use_users_timezone"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # visible_options -> Name, Official code, E-mail, Picture, Login, Password, Language, Phone, Theme
    When I fill in the following:
      | search_keyword | visible_options |
    And I press "search_search"
    And I wait for the element "[name='form_visible_options']" to appear
    And I select "Name" from "form_visible_options"
    And I additionally select "Official code" from "form_visible_options"
    And I additionally select "E-mail" from "form_visible_options"
    And I additionally select "Picture" from "form_visible_options"
    And I additionally select "Login" from "form_visible_options"
    And I additionally select "Password" from "form_visible_options"
    And I additionally select "Language" from "form_visible_options"
    And I additionally select "Phone" from "form_visible_options"
    And I additionally select "Theme" from "form_visible_options"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # changeable_options -> same set
    When I fill in the following:
      | search_keyword | changeable_options |
    And I press "search_search"
    And I wait for the element "[name='form_changeable_options']" to appear
    And I select "Name" from "form_changeable_options"
    And I additionally select "Official code" from "form_changeable_options"
    And I additionally select "E-mail" from "form_changeable_options"
    And I additionally select "Picture" from "form_changeable_options"
    And I additionally select "Login" from "form_changeable_options"
    And I additionally select "Password" from "form_changeable_options"
    And I additionally select "Language" from "form_changeable_options"
    And I additionally select "Phone" from "form_changeable_options"
    And I additionally select "Theme" from "form_changeable_options"
    And I click the "i.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 2 — Quiz and exercise settings
  # ==============================================================
  Scenario: Teardown — quiz and exercise settings

    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear

    # allow_quiz_results_page_config -> No
    When I fill in the following:
      | platform_management_search | allow_quiz_results_page_config |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_allow_quiz_results_page_config']" to appear
    And I select "No" from "form_allow_quiz_results_page_config"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # show_exercise_expected_choice -> No
    When I fill in the following:
      | search_keyword | show_exercise_expected_choice |
    And I press "search_search"
    And I wait for the element "[name='form_show_exercise_expected_choice']" to appear
    And I select "No" from "form_show_exercise_expected_choice"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # hide_free_question_score -> No
    When I fill in the following:
      | search_keyword | hide_free_question_score |
    And I press "search_search"
    And I wait for the element "[name='form_hide_free_question_score']" to appear
    And I select "No" from "form_hide_free_question_score"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_notification_setting_per_exercise -> No
    When I fill in the following:
      | search_keyword | allow_notification_setting_per_exercise |
    And I press "search_search"
    And I wait for the element "[name='form_allow_notification_setting_per_exercise']" to appear
    And I select "No" from "form_allow_notification_setting_per_exercise"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # quiz_show_description_on_results_page -> No
    When I fill in the following:
      | search_keyword | quiz_show_description_on_results_page |
    And I press "search_search"
    And I wait for the element "[name='form_quiz_show_description_on_results_page']" to appear
    And I select "No" from "form_quiz_show_description_on_results_page"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_quiz_question_feedback -> No
    When I fill in the following:
      | search_keyword | allow_quiz_question_feedback |
    And I press "search_search"
    And I wait for the element "[name='form_allow_quiz_question_feedback']" to appear
    And I select "No" from "form_allow_quiz_question_feedback"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # exercise_hide_label -> No
    When I fill in the following:
      | search_keyword | exercise_hide_label |
    And I press "search_search"
    And I wait for the element "[name='form_exercise_hide_label']" to appear
    And I select "No" from "form_exercise_hide_label"
    And I click the "i.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 3 — Ticket, social and skills
  # ==============================================================
  Scenario: Teardown — ticket, social and skills settings

    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear

    # show_link_ticket_notification -> No
    When I fill in the following:
      | platform_management_search | show_link_ticket_notification |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_show_link_ticket_notification']" to appear
    And I select "No" from "form_show_link_ticket_notification"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # ticket_project_user_roles -> empty
    When I fill in the following:
      | search_keyword | ticket_project_user_roles |
    And I press "search_search"
    And I wait for the element "[name='form_ticket_project_user_roles']" to appear
    And I fill in the following:
      | form_ticket_project_user_roles | "" |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # ticket_send_warning_to_all_admins -> No
    When I fill in the following:
      | search_keyword | ticket_send_warning_to_all_admins |
    And I press "search_search"
    And I wait for the element "[name='form_ticket_send_warning_to_all_admins']" to appear
    And I select "No" from "form_ticket_send_warning_to_all_admins"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # ticket_allow_student_add -> No
    When I fill in the following:
      | search_keyword | ticket_allow_student_add |
    And I press "search_search"
    And I wait for the element "[name='form_ticket_allow_student_add']" to appear
    And I select "No" from "form_ticket_allow_student_add"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # ticket_allow_category_edition -> No (requires re-navigation to /admin)
    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear
    When I fill in the following:
      | platform_management_search | ticket_allow_category_edition |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_ticket_allow_category_edition']" to appear
    And I select "No" from "form_ticket_allow_category_edition"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # social_show_language_flag_in_profile -> No
    When I fill in the following:
      | search_keyword | social_show_language_flag_in_profile |
    And I press "search_search"
    And I wait for the element "[name='form_social_show_language_flag_in_profile']" to appear
    And I select "No" from "form_social_show_language_flag_in_profile"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # disable_dislike_option -> No
    When I fill in the following:
      | search_keyword | disable_dislike_option |
    And I press "search_search"
    And I wait for the element "[name='form_disable_dislike_option']" to appear
    And I select "No" from "form_disable_dislike_option"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # social_enable_messages_feedback -> No
    When I fill in the following:
      | search_keyword | social_enable_messages_feedback |
    And I press "search_search"
    And I wait for the element "[name='form_social_enable_messages_feedback']" to appear
    And I select "No" from "form_social_enable_messages_feedback"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # manual_assignment_subskill_autoload -> No
    When I fill in the following:
      | search_keyword | manual_assignment_subskill_autoload |
    And I press "search_search"
    And I wait for the element "[name='form_manual_assignment_subskill_autoload']" to appear
    And I select "No" from "form_manual_assignment_subskill_autoload"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # skill_levels_names -> default JSON
    When I fill in the following:
      | search_keyword | skill_levels_names |
    And I press "search_search"
    And I wait for the element "[name='form_skill_levels_names']" to appear
    And I fill in the following:
      | form_skill_levels_names | {"levels":{"1":"Skills","2":"Capability","3":"Dimension"}} |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_teacher_access_student_skills -> No
    When I fill in the following:
      | search_keyword | allow_teacher_access_student_skills |
    And I press "search_search"
    And I wait for the element "[name='form_allow_teacher_access_student_skills']" to appear
    And I select "No" from "form_allow_teacher_access_student_skills"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # badge_assignation_notification -> No
    When I fill in the following:
      | search_keyword | badge_assignation_notification |
    And I press "search_search"
    And I wait for the element "[name='form_badge_assignation_notification']" to appear
    And I select "No" from "form_badge_assignation_notification"
    And I click the "i.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 4 — Sessions and registration / security
  # ==============================================================
  Scenario: Teardown — session and registration settings

    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear

    # session_model_list_field_ordered_by_id -> No
    When I fill in the following:
      | platform_management_search | session_model_list_field_ordered_by_id |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_session_model_list_field_ordered_by_id']" to appear
    And I select "No" from "form_session_model_list_field_ordered_by_id"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # session_admins_access_all_content -> No
    When I fill in the following:
      | search_keyword | session_admins_access_all_content |
    And I press "search_search"
    And I wait for the element "[name='form_session_admins_access_all_content']" to appear
    And I select "No" from "form_session_admins_access_all_content"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # session_list_show_count_users -> No
    When I fill in the following:
      | search_keyword | session_list_show_count_users |
    And I press "search_search"
    And I wait for the element "[name='form_session_list_show_count_users']" to appear
    And I select "No" from "form_session_list_show_count_users"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # session_admins_edit_courses_content -> No
    When I fill in the following:
      | search_keyword | session_admins_edit_courses_content |
    And I press "search_search"
    And I wait for the element "[name='form_session_admins_edit_courses_content']" to appear
    And I select "No" from "form_session_admins_edit_courses_content"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_search_diagnostic -> Yes
    When I fill in the following:
      | search_keyword | allow_search_diagnostic |
    And I press "search_search"
    And I wait for the element "[name='form_allow_search_diagnostic']" to appear
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_session_admins_to_manage_all_sessions -> No
    When I fill in the following:
      | search_keyword | allow_session_admins_to_manage_all_sessions |
    And I press "search_search"
    And I wait for the element "[name='form_allow_session_admins_to_manage_all_sessions']" to appear
    And I select "No" from "form_allow_session_admins_to_manage_all_sessions"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # anonymous_autoprovisioning -> No
    When I fill in the following:
      | search_keyword | anonymous_autoprovisioning |
    And I press "search_search"
    And I wait for the element "[name='form_anonymous_autoprovisioning']" to appear
    And I select "No" from "form_anonymous_autoprovisioning"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # captcha_time_to_block -> empty
    When I fill in the following:
      | search_keyword | captcha_time_to_block |
    And I press "search_search"
    And I wait for the element "[name='form_captcha_time_to_block']" to appear
    And I fill in the following:
      | form_captcha_time_to_block | "" |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # captcha_number_mistakes_to_block_account -> empty
    When I fill in the following:
      | search_keyword | captcha_number_mistakes_to_block_account |
    And I press "search_search"
    And I wait for the element "[name='form_captcha_number_mistakes_to_block_account']" to appear
    And I fill in the following:
      | form_captcha_number_mistakes_to_block_account | "" |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # hide_legal_accept_checkbox -> No
    When I fill in the following:
      | search_keyword | hide_legal_accept_checkbox |
    And I press "search_search"
    And I wait for the element "[name='form_hide_legal_accept_checkbox']" to appear
    And I select "No" from "form_hide_legal_accept_checkbox"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # redirect_after_login -> default JSON
    When I fill in the following:
      | search_keyword | redirect_after_login |
    And I press "search_search"
    And I wait for the element "[name='form_redirect_after_login']" to appear
    And I fill in the following:
      | form_redirect_after_login | {"COURSEMANAGER":"courses","STUDENT":"courses","DRH":"","SESSIONADMIN":"admin-dashboard","STUDENT_BOSS":"main/my_space/student.php","INVITEE":"courses","ADMIN":"admin"} |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # send_inscription_msg_to_inbox -> No
    When I fill in the following:
      | search_keyword | send_inscription_msg_to_inbox |
    And I press "search_search"
    And I wait for the element "[name='form_send_inscription_msg_to_inbox']" to appear
    And I select "No" from "form_send_inscription_msg_to_inbox"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_fields_inscription -> empty
    When I fill in the following:
      | search_keyword | allow_fields_inscription |
    And I press "search_search"
    And I wait for the element "[name='form_allow_fields_inscription']" to appear
    And I fill in the following:
      | form_allow_fields_inscription | "" |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # required_extra_fields_in_inscription -> empty
    When I fill in the following:
      | search_keyword | required_extra_fields_in_inscription |
    And I press "search_search"
    And I wait for the element "[name='form_required_extra_fields_in_inscription']" to appear
    And I fill in the following:
      | form_required_extra_fields_in_inscription | "" |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_terms_conditions -> Yes
    When I fill in the following:
      | search_keyword | allow_terms_conditions |
    And I press "search_search"
    And I wait for the element "[name='form_allow_terms_conditions']" to appear
    And I select "Yes" from "form_allow_terms_conditions"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_registration_as_teacher -> Yes
    When I fill in the following:
      | search_keyword | allow_registration_as_teacher |
    And I press "search_search"
    And I wait for the element "[name='form_allow_registration_as_teacher']" to appear
    And I select "Yes" from "form_allow_registration_as_teacher"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_registration -> Approval
    When I fill in the following:
      | search_keyword | allow_registration |
    And I press "search_search"
    And I wait for the element "[name='form_allow_registration']" to appear
    And I select "Approval" from "form_allow_registration"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # cookie_warning -> No
    When I fill in the following:
      | search_keyword | cookie_warning |
    And I press "search_search"
    And I wait for the element "[name='form_cookie_warning']" to appear
    And I select "No" from "form_cookie_warning"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_send_message_to_all_platform_users -> No
    When I fill in the following:
      | search_keyword | allow_send_message_to_all_platform_users |
    And I press "search_search"
    And I wait for the element "[name='form_allow_send_message_to_all_platform_users']" to appear
    And I select "No" from "form_allow_send_message_to_all_platform_users"
    And I click the "i.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error

  # ==============================================================
  # SCENARIO 5 — LP, forum, active tools, UI and tabs
  # ==============================================================
  Scenario: Teardown — LP, forum, UI and tabs settings

    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear

    # force_edit_exercise_in_lp -> No
    When I fill in the following:
      | platform_management_search | force_edit_exercise_in_lp |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_force_edit_exercise_in_lp']" to appear
    And I select "No" from "form_force_edit_exercise_in_lp"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # ticket_lp_quiz_info_add -> No
    When I fill in the following:
      | search_keyword | ticket_lp_quiz_info_add |
    And I press "search_search"
    And I wait for the element "[name='form_ticket_lp_quiz_info_add']" to appear
    And I select "No" from "form_ticket_lp_quiz_info_add"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # show_hidden_exercise_added_to_lp -> Yes
    When I fill in the following:
      | search_keyword | show_hidden_exercise_added_to_lp |
    And I press "search_search"
    And I wait for the element "[name='form_show_hidden_exercise_added_to_lp']" to appear
    And I select "Yes" from "form_show_hidden_exercise_added_to_lp"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # validate_lp_prerequisite_from_other_session -> No
    When I fill in the following:
      | search_keyword | validate_lp_prerequisite_from_other_session |
    And I press "search_search"
    And I wait for the element "[name='form_validate_lp_prerequisite_from_other_session']" to appear
    And I select "No" from "form_validate_lp_prerequisite_from_other_session"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_course_multiple_languages -> No
    When I fill in the following:
      | search_keyword | allow_course_multiple_languages |
    And I press "search_search"
    And I wait for the element "[name='form_allow_course_multiple_languages']" to appear
    And I select "No" from "form_allow_course_multiple_languages"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # subscribe_users_to_forum_notifications_also_in_base_course -> No
    When I fill in the following:
      | search_keyword | subscribe_users_to_forum_notifications_also_in_base_course |
    And I press "search_search"
    And I wait for the element "[name='form_subscribe_users_to_forum_notifications_also_in_base_course']" to appear
    And I select "No" from "form_subscribe_users_to_forum_notifications_also_in_base_course"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_forum_category_language_filter -> No
    When I fill in the following:
      | search_keyword | allow_forum_category_language_filter |
    And I press "search_search"
    And I wait for the element "[name='form_allow_forum_category_language_filter']" to appear
    And I select "No" from "form_allow_forum_category_language_filter"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # hide_forum_post_revision_language -> No
    When I fill in the following:
      | search_keyword | hide_forum_post_revision_language |
    And I press "search_search"
    And I wait for the element "[name='form_hide_forum_post_revision_language']" to appear
    And I select "No" from "form_hide_forum_post_revision_language"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_forum_post_revisions -> No
    When I fill in the following:
      | search_keyword | allow_forum_post_revisions |
    And I press "search_search"
    And I wait for the element "[name='form_allow_forum_post_revisions']" to appear
    And I select "No" from "form_allow_forum_post_revisions"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # translate_html -> No
    When I fill in the following:
      | search_keyword | translate_html |
    And I press "search_search"
    And I wait for the element "[name='form_translate_html']" to appear
    And I select "No" from "form_translate_html"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # form_enable_help_link -> Yes
    When I fill in the following:
      | search_keyword | form_enable_help_link |
    And I press "search_search"
    And I wait for the element "[name='form_enable_help_link']" to appear
    And I select "Yes" from "form_enable_help_link"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # active_tools_on_create -> re-check all 31 checkboxes
    When I fill in the following:
      | search_keyword | active_tools_on_create |
    And I press "search_search"
    And I wait for the element "#form_active_tools_on_create_0" to appear
    And I click the "#form_active_tools_on_create_0" element
    And I click the "#form_active_tools_on_create_1" element
    And I click the "#form_active_tools_on_create_2" element
    And I click the "#form_active_tools_on_create_3" element
    And I click the "#form_active_tools_on_create_4" element
    And I click the "#form_active_tools_on_create_5" element
    And I click the "#form_active_tools_on_create_6" element
    And I click the "#form_active_tools_on_create_7" element
    And I click the "#form_active_tools_on_create_8" element
    And I click the "#form_active_tools_on_create_9" element
    And I click the "#form_active_tools_on_create_10" element
    And I click the "#form_active_tools_on_create_11" element
    And I click the "#form_active_tools_on_create_12" element
    And I click the "#form_active_tools_on_create_13" element
    And I click the "#form_active_tools_on_create_14" element
    And I click the "#form_active_tools_on_create_15" element
    And I click the "#form_active_tools_on_create_16" element
    And I click the "#form_active_tools_on_create_17" element
    And I click the "#form_active_tools_on_create_18" element
    And I click the "#form_active_tools_on_create_19" element
    And I click the "#form_active_tools_on_create_20" element
    And I click the "#form_active_tools_on_create_21" element
    And I click the "#form_active_tools_on_create_22" element
    And I click the "#form_active_tools_on_create_23" element
    And I click the "#form_active_tools_on_create_24" element
    And I click the "#form_active_tools_on_create_25" element
    And I click the "#form_active_tools_on_create_26" element
    And I click the "#form_active_tools_on_create_27" element
    And I click the "#form_active_tools_on_create_28" element
    And I click the "#form_active_tools_on_create_29" element
    And I click the "#form_active_tools_on_create_30" element
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # allow_general_certificate -> No
    When I fill in the following:
      | search_keyword | allow_general_certificate |
    And I press "search_search"
    And I wait for the element "[name='form_allow_general_certificate']" to appear
    And I select "No" from "form_allow_general_certificate"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # hide_my_certificate_link -> No
    When I fill in the following:
      | search_keyword | hide_my_certificate_link |
    And I press "search_search"
    And I wait for the element "[name='form_hide_my_certificate_link']" to appear
    And I select "No" from "form_hide_my_certificate_link"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # show_courses_sessions -> Hide catalogue
    When I fill in the following:
      | search_keyword | show_courses_sessions |
    And I press "search_search"
    And I wait for the element "[name='form_show_courses_sessions']" to appear
    And I select "Hide catalogue" from "form_show_courses_sessions"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # course_catalog_display_in_home -> No
    When I fill in the following:
      | search_keyword | course_catalog_display_in_home |
    And I press "search_search"
    And I wait for the element "[name='form_course_catalog_display_in_home']" to appear
    And I select "No" from "form_course_catalog_display_in_home"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # Multiple anonymous users -> 100
    And I am on "/admin"
    And I wait for the element "#platform_management_search" to appear
    When I fill in the following:
      | platform_management_search | Multiple anonymous users |
    And I press "platform_management_search_button"
    And I wait for the element "[name='form_max_anonymous_users']" to appear
    And I fill in the following:
      | form_max_anonymous_users | 100 |
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # tabs -> default JSON (diagnostics=false, videoconference=false)
    When I fill in the following:
      | search_keyword | tabs |
    And I press "search_search"
    And I wait for the element "[name='form_show_tabs']" to appear
    And I zoom out to maximum
    And I fill in "form_show_tabs" with "{\"menu\":{\"campus_homepage\":true,\"my_courses\":true,\"reporting\":true,\"platform_administration\":true,\"my_agenda\":true,\"social\":true,\"videoconference\":false,\"diagnostics\":false,\"catalogue\":true,\"session_admin\":true,\"search\":true,\"question_manager\":false},\"topbar\":{\"topbar_my_certificates\":true,\"topbar_my_custom_certificate\":false,\"topbar_skills\":true}}"
    And I click the "i.mdi-content-save" element
    And I wait for the element "[name='search_keyword']" to appear

    # Diagnostic -> Yes (final confirmation)
    When I fill in the following:
      | search_keyword | Diagnostic |
    And I press "search_search"
    And I wait for the element "[name='form_allow_search_diagnostic']" to appear
    And I select "Yes" from "form_allow_search_diagnostic"
    And I click the "i.mdi-content-save" element
    And I wait very long for the page to be loaded
    Then I should not see an error
