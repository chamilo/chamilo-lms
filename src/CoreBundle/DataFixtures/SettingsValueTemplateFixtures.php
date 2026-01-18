<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsValueTemplate;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

use const JSON_PRETTY_PRINT;

class SettingsValueTemplateFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['settings-value-template'];
    }

    /**
     * Returns the list of JSON templates grouped by category.
     */
    public static function getTemplatesGrouped(): array
    {
        return [
            'search' => [
                [
                    'variable' => 'search_prefilter_prefix',
                    'json_example' => [
                        'fields' => [
                            [
                                'code' => 'T',
                                'title' => 'Title',
                            ],
                            [
                                'code' => 'D',
                                'title' => 'Description',
                            ],
                        ],
                    ],
                ],
            ],
            'forum' => [
                [
                    'variable' => 'community_managers_user_list',
                    'json_example' => [
                        'users' => [1],
                    ],
                ],
            ],
            'tracking' => [
                [
                    'variable' => 'my_progress_course_tools_order',
                    'json_example' => [
                        'order' => ['quizzes', 'learning_paths', 'skills'],
                    ],
                ],
            ],
            'display' => [
                [
                    'variable' => 'show_tabs',
                    'json_example' => [
                        'menu' => [
                            'campus_homepage' => true,
                            'my_courses' => true,
                            'reporting' => true,
                            'platform_administration' => true,
                            'my_agenda' => true,
                            'social' => true,
                            'videoconference' => false,
                            'diagnostics' => false,
                            'catalogue' => true,
                            'session_admin' => true,
                            'search' => true,
                            'question_manager' => false,
                        ],
                        'topbar' => [
                            'topbar_certificate' => true,
                            'topbar_skills' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'show_tabs_per_role',
                    'json_example' => [
                        'SESSIONADMIN' => ['session_admin', 'my_courses'],
                        'ADMIN' => ['platform_administration'],
                    ],
                ],
                [
                    'variable' => 'table_row_list',
                    'json_example' => [
                        'options' => [50, 100, 200, 500],
                    ],
                ],
            ],
            'catalog' => [
                [
                    'variable' => 'only_show_course_from_selected_category',
                    'json_example' => ['Cat1', 'Cat2'],
                ],
                [
                    'variable' => 'course_catalog_settings',
                    'json_example' => [
                        'link_settings' => [
                            'info_url' => 'course_description_popup',
                            'title_url' => 'course_home',
                            'image_url' => 'course_about',
                        ],
                        'hide_course_title' => false,
                        'search_by_title' => true,
                        'redirect_after_subscription' => 'course_home',
                        'extra_fields_in_search_form' => ['variable1', 'variable2'],
                        'extra_fields_in_course_block' => ['variable3', 'variable4'],
                        'standard_sort_options' => [
                            'title' => 1,
                            'creation_date' => -1,
                            'count_users' => -1,
                            'point_info/point_average' => -1,
                            'point_info/total_score' => -1,
                            'point_info/users' => -1,
                        ],
                        'extra_field_sort_options' => [
                            'variable5' => -1,
                            'variable6' => 1,
                        ],
                        'pre_filter_on_language' => 1,
                    ],
                ],
                [
                    'variable' => 'session_catalog_settings',
                    'json_example' => [
                        'by_title' => true,
                        'by_date' => true,
                        'by_tag' => true,
                        'show_session_info' => true,
                        'show_session_date' => true,
                    ],
                ],
            ],
            'platform' => [
                [
                    'variable' => 'push_notification_settings',
                    'json_example' => [
                        'gotify_url' => 'http://localhost:8080',
                        'gotify_token' => 'A0yWWfe_8YRLv_B',
                        'enabled' => true,
                        'vapid_public_key' => 'BNg54MTyDZSdyFq99EmppT606jKVDS5o7jGVxMLW3Qir937A98sxtrK4VMt1ddNlK93MUenK0kM3aiAMu9HRcjQ=',
                        'vapid_private_key' => 'UgS5-xSneOcSyNJVq4c9wmEGaCoE1Y8oh-7ZGXPgs8o',
                    ],
                ],
                [
                    'variable' => 'user_status_show_option',
                    'json_example' => [
                        'COURSEMANAGER' => true,
                        'STUDENT' => true,
                        'DRH' => false,
                        'SESSIONADMIN' => false,
                        'STUDENT_BOSS' => false,
                        'INVITEE' => false,
                    ],
                ],
            ],
            'agenda' => [
                [
                    'variable' => 'agenda_legend',
                    'json_example' => [
                        'red' => 'red caption',
                        '#f0f' => 'another caption',
                    ],
                ],
                [
                    'variable' => 'agenda_colors',
                    'json_example' => [
                        'platform' => 'red',
                        'course' => '#458B00',
                        'group' => '#A0522D',
                        'session' => '#00496D',
                        'other_session' => '#999',
                        'personal' => 'steel blue',
                        'student_publication' => '#FF8C00',
                    ],
                ],
                [
                    'variable' => 'agenda_on_hover_info',
                    'json_example' => [
                        'options' => [
                            'comment' => true,
                            'description' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'fullcalendar_settings',
                    'json_example' => [
                        'settings' => [
                            'businessHours' => [
                                'dow' => [0, 1, 2, 3, 4],
                                'start' => '10:00',
                                'end' => '18:00',
                            ],
                            'firstDay' => 0,
                        ],
                    ],
                ],
            ],
            'admin' => [
                [
                    'variable' => 'user_status_option_show_only_for_admin',
                    'json_example' => [
                        'COURSEMANAGER' => false,
                        'STUDENT' => false,
                        'DRH' => false,
                        'SESSIONADMIN' => true,
                        'STUDENT_BOSS' => false,
                        'INVITEE' => false,
                    ],
                ],
            ],
            'aihelpers' => [
                [
                    'variable' => 'ai_providers',
                    'json_example' => [
                        'openai' => [
                            'api_key' => 'your-key',
                            'organization_id' => 'org123',
                            'monthly_token_limit' => 10000,
                            'text' => [
                                'url' => 'https://api.openai.com/v1/chat/completions',
                                'model' => 'gpt-4o',
                                'temperature' => 0.7,
                            ],
                            'image' => [
                                'url' => 'https://api.openai.com/v1/images/generations',
                                'model' => 'dall-e-3',
                                'size' => '1024x1024',
                                'quality' => 'standard',
                            ],
                            'video' => [
                                'url' => 'https://api.openai.com/v1/videos/generations',
                                'model' => 'sora-1.0',
                                'duration' => 10,
                                'resolution' => '1920x1080',
                            ],
                            'document' => [
                                'url' => 'https://api.openai.com/v1/chat/completions',
                                'model' => 'gpt-4o',
                                'temperature' => 0.7,
                            ],
                            'document_process' => [
                                'upload_url' => 'https://api.openai.com/v1/files',
                                'purpose' => 'assistants',
                                'query_url' => 'https://api.openai.com/v1/threads',
                                'model' => 'gpt-4o',
                                'temperature' => 0.7,
                            ],
                        ],
                        'deepseek' => [
                            'api_key' => 'your-key',
                            'organization_id' => 'org456',
                            'monthly_token_limit' => 5000,
                            'text' => [
                                'url' => 'https://api.deepseek.com/chat/completions',
                                'model' => 'deepseek-chat',
                                'temperature' => 0.7,
                            ],
                            'document' => [
                                'url' => 'https://api.deepseek.com/chat/completions',
                                'model' => 'deepseek-chat',
                                'temperature' => 0.7,
                            ],
                        ],
                        'grok' => [
                            'api_key' => 'your-key',
                            'text' => [
                                'url' => 'https://api.x.ai/v1/responses',
                                'model' => 'grok-4-1-fast-reasoning',
                                'temperature' => 0.7,
                            ],
                            'image' => [
                                'url' => 'https://api.x.ai/v1/images/generations',
                                'model' => 'grok-2-image',
                                'response_format' => 'base64',
                            ],
                            'document' => [
                                'url' => 'https://api.x.ai/v1/responses',
                                'model' => 'grok-4-1-fast-reasoning',
                                'temperature' => 0.7,
                                'format' => 'pdf',
                            ],
                            'document_process' => [
                                'upload_url' => 'https://api.x.ai/v1/files',
                                'query_url' => 'https://api.x.ai/v1/responses',
                                'model' => 'grok-4-1-fast-reasoning',
                                'temperature' => 0.7,
                                'max_file_size_mb' => 30,
                            ],
                        ],
                        'mistral' => [
                            'api_key' => 'your-key',
                            'text' => [
                                'url' => 'https://api.mistral.ai/v1/chat/completions',
                                'model' => 'mistral-large-latest',
                                'temperature' => 0.7,
                            ],
                            'document' => [
                                'url' => 'https://api.mistral.ai/v1/chat/completions',
                                'model' => 'mistral-large-latest',
                                'temperature' => 0.7,
                            ],
                            'document_process' => [
                                'upload_url' => 'https://api.mistral.ai/v1/files',
                                'ocr_url' => 'https://api.mistral.ai/v1/ocr',
                                'query_url' => 'https://api.mistral.ai/v1/chat/completions',
                                'model' => 'mistral-large-latest',
                                'temperature' => 0.7,
                            ],
                        ],
                        'gemini' => [
                            'api_key' => 'your-key',
                            'text' => [
                                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                                'model' => 'gemini-2.5-flash',
                                'temperature' => 0.7,
                            ],
                            'image' => [
                                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                                'model' => 'imagen-3',
                                'size' => '1024x1024',
                            ],
                            'video' => [
                                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                                'model' => 'veo-3.1',
                                'duration' => 10,
                            ],
                            'document' => [
                                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                                'model' => 'gemini-2.5-flash',
                                'temperature' => 0.7,
                            ],
                            'document_process' => [
                                'upload_url' => 'https://generativelanguage.googleapis.com/upload/v1beta/files',
                                'query_url' => 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                                'model' => 'gemini-2.5-flash',
                                'temperature' => 0.7,
                            ],
                        ],
                    ],
                ],
            ],
            'work' => [
                [
                    'variable' => 'compilatio_tool',
                    'json_example' => [
                        'settings' => [
                            'key' => '',
                            'soap_url' => '',
                            'proxy_host' => '',
                            'proxy_port' => '',
                            'max_filesize' => '',
                            'transport_mode' => '',
                            'wget_uri' => '',
                            'wget_login' => '',
                            'wget_password' => '',
                        ],
                    ],
                ],
            ],
            'workflows' => [
                [
                    'variable' => 'send_all_emails_to',
                    'json_example' => [
                        'emails' => [
                            'admin1@example.com',
                            'admin2@example.com',
                        ],
                    ],
                ],
                [
                    'variable' => 'update_student_expiration_x_date',
                    'json_example' => [
                        'days' => 0,
                        'months' => 0,
                    ],
                ],
                [
                    'variable' => 'user_number_of_days_for_default_expiration_date_per_role',
                    'json_example' => [
                        'COURSEMANAGER' => 365,
                        'STUDENT' => 31,
                        'DRH' => 31,
                        'SESSIONADMIN' => 60,
                        'STUDENT_BOSS' => 60,
                        'INVITEE' => 31,
                    ],
                ],
            ],
            'course' => [
                [
                    'variable' => 'course_log_hide_columns',
                    'json_example' => ['columns' => [1, 9]],
                ],
                [
                    'variable' => 'course_student_info',
                    'json_example' => [
                        'score' => false,
                        'progress' => false,
                        'certificate' => false,
                    ],
                ],
                [
                    'variable' => 'course_log_default_extra_fields',
                    'json_example' => ['extra_fields' => ['office_address', 'office_phone_extension']],
                ],
                [
                    'variable' => 'course_creation_by_teacher_extra_fields_to_show',
                    'json_example' => ['fields' => ['ExtrafieldLabel1', 'ExtrafieldLabel2']],
                ],
                [
                    'variable' => 'course_creation_form_set_extra_fields_mandatory',
                    'json_example' => ['fields' => ['fieldLabel1', 'fieldLabel2']],
                ],
                [
                    'variable' => 'course_configuration_tool_extra_fields_to_show_and_edit',
                    'json_example' => ['fields' => ['ExtrafieldLabel1', 'ExtrafieldLabel2']],
                ],
                [
                    'variable' => 'course_creation_user_course_extra_field_relation_to_prefill',
                    'json_example' => [
                        'fields' => [
                            'CourseExtrafieldLabel1' => 'UserExtrafieldLabel1',
                            'CourseExtrafieldLabel2' => 'UserExtrafieldLabel2',
                        ],
                    ],
                ],
            ],
            'document' => [
                [
                    'variable' => 'video_features',
                    'json_example' => [
                        'features' => ['speed'],
                    ],
                ],
                [
                    'variable' => 'documents_custom_cloud_link_list',
                    'json_example' => [
                        'links' => ['example.com', 'example2.com'],
                    ],
                ],
            ],
            'editor' => [
                [
                    'variable' => 'editor_driver_list',
                    'json_example' => ['PersonalDriver', 'CourseDriver'],
                ],
                [
                    'variable' => 'editor_settings',
                    'json_example' => [
                        'config' => [
                            'youtube_responsive' => true,
                            'image_responsive' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'video_player_renderers',
                    'json_example' => [
                        'renderers' => ['dailymotion', 'facebook', 'twitch', 'vimeo', 'youtube'],
                    ],
                ],
            ],
            'exercise' => [
                [
                    'variable' => 'exercise_additional_teacher_modify_actions',
                    'json_example' => [
                        'myplugin' => ['MyPlugin', 'urlGeneratorCallback'],
                    ],
                ],
                [
                    'variable' => 'quiz_image_zoom',
                    'json_example' => [
                        'options' => [
                            'zoomWindowWidth' => 400,
                            'zoomWindowHeight' => 400,
                        ],
                    ],
                ],
                [
                    'variable' => 'add_exercise_best_attempt_in_report',
                    'json_example' => [
                        'courses' => [
                            'ABC' => [88, 89],
                        ],
                    ],
                ],
                [
                    'variable' => 'exercise_category_report_user_extra_fields',
                    'json_example' => [
                        'fields' => ['skype', 'rssfeeds'],
                    ],
                ],
                [
                    'variable' => 'score_grade_model',
                    'json_example' => [
                        'models' => [
                            [
                                'id' => 1,
                                'variable' => 'My score grading model',
                                'display_score_name' => 0,
                                'score_list' => [
                                    [
                                        'variable' => 'Very bad',
                                        'css_class' => 'btn-danger',
                                        'min' => 0,
                                        'max' => 20,
                                        'score_to_qualify' => 0,
                                    ],
                                    [
                                        'variable' => 'Bad',
                                        'css_class' => 'btn-danger',
                                        'min' => 21,
                                        'max' => 50,
                                        'score_to_qualify' => 25,
                                    ],
                                    [
                                        'variable' => 'Good',
                                        'css_class' => 'btn-warning',
                                        'min' => 51,
                                        'max' => 70,
                                        'score_to_qualify' => 60,
                                    ],
                                    [
                                        'variable' => 'Very good',
                                        'css_class' => 'btn-success',
                                        'min' => 71,
                                        'max' => 100,
                                        'score_to_qualify' => 100,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'variable' => 'exercise_embeddable_extra_types',
                    'json_example' => [
                        'types' => [],
                    ],
                ],
            ],
            'gradebook' => [
                [
                    'variable' => 'gradebook_dependency_mandatory_courses',
                    'json_example' => [
                        'courses' => [1, 2],
                    ],
                ],
                [
                    'variable' => 'gradebook_badge_sidebar',
                    'json_example' => [
                        'gradebooks' => [1, 2, 3],
                    ],
                ],
                [
                    'variable' => 'gradebook_flatview_extrafields_columns',
                    'json_example' => [
                        'variables' => [],
                    ],
                ],
                [
                    'variable' => 'gradebook_pdf_export_settings',
                    'json_example' => [
                        'hide_score_weight' => true,
                        'hide_feedback_textarea' => true,
                    ],
                ],
                [
                    'variable' => 'gradebook_display_extra_stats',
                    'json_example' => [
                        'columns' => [1, 2, 3],
                    ],
                ],
            ],
            'learningpath' => [
                [
                    'variable' => 'lp_subscription_settings',
                    'json_example' => [
                        'options' => [
                            'allow_add_users_to_lp' => true,
                            'allow_add_users_to_lp_category' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'lp_view_settings',
                    'json_example' => [
                        'display' => [
                            'show_reporting_icon' => true,
                            'hide_lp_arrow_navigation' => false,
                            'show_toolbar_by_default' => false,
                            'navigation_in_the_middle' => false,
                        ],
                    ],
                ],
                [
                    'variable' => 'download_files_after_all_lp_finished',
                    'json_example' => [
                        'courses' => [
                            'ABC' => [1, 100],
                        ],
                    ],
                ],
            ],
            'mail' => [
                [
                    'variable' => 'cron_notification_help_desk',
                    'json_example' => [
                        'emails' => [
                            'email@example.com',
                            'email2@example.com',
                        ],
                    ],
                ],
                [
                    'variable' => 'notifications_extended_footer_message',
                    'json_example' => [
                        'english' => [
                            'paragraphs' => [
                                'Change or delete this paragraph or add another one',
                            ],
                        ],
                    ],
                ],
                [
                    'variable' => 'mailer_dkim',
                    'json_example' => [
                        'enable' => 1,
                        'selector' => 'chamilo',
                        'domain' => 'mydomain.com',
                        'private_key_string' => '',
                        'private_key' => '',
                        'passphrase' => '',
                    ],
                ],
                [
                    'variable' => 'mailer_xoauth2',
                    'json_example' => [
                        'method' => false,
                        'url_authorize' => 'https://provider.example/oauth2/auth',
                        'url_access_token' => 'https://provider.example/token',
                        'url_resource_owner_details' => 'https://provider.example/userinfo',
                        'scopes' => '',
                        'client_id' => '',
                        'client_secret' => '',
                        'refresh_token' => '',
                    ],
                ],
            ],
            'profile' => [
                [
                    'variable' => 'hide_user_field_from_list',
                    'json_example' => [
                        'fields' => ['username'],
                    ],
                ],
                [
                    'variable' => 'send_notification_when_user_added',
                    'json_example' => [
                        'admins' => [1],
                    ],
                ],
                [
                    'variable' => 'show_conditions_to_user',
                    'json_example' => [
                        'conditions' => [
                            [
                                'variable' => 'gdpr',
                                'display_text' => 'GDPRTitle',
                                'text_area' => 'GDPRTextArea',
                            ],
                            [
                                'variable' => 'my_terms',
                                'display_text' => 'My test conditions',
                                'text_area' => 'This is a long text area, with lot of terms and conditions ... ',
                            ],
                        ],
                    ],
                ],
                [
                    'variable' => 'profile_fields_visibility',
                    'json_example' => [
                        'options' => [
                            'vcard' => false,
                            'firstname' => true,
                            'lastname' => true,
                            'photo' => true,
                            'email' => false,
                            'language' => true,
                            'chat' => true,
                            'terms_ville' => true,
                            'terms_datedenaissance' => true,
                            'terms_paysresidence' => false,
                            'filiere_user' => true,
                            'terms_villedustage' => true,
                            'hobbies' => true,
                            'langue_cible' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'user_import_settings',
                    'json_example' => [
                        'options' => [
                            'send_mail_default_option' => '1',
                        ],
                    ],
                ],
                [
                    'variable' => 'user_search_on_extra_fields',
                    'json_example' => [
                        'extra_fields' => ['variable1', 'variable2'],
                    ],
                ],
                [
                    'variable' => 'allow_social_map_fields',
                    'json_example' => [
                        'fields' => ['terms_villedustage', 'terms_ville'],
                    ],
                ],
            ],
            'registration' => [
                [
                    'variable' => 'extldap_config',
                    'json_example' => [
                        'host' => '',
                        'port' => '',
                    ],
                ],
                [
                    'variable' => 'required_extra_fields_in_inscription',
                    'json_example' => [
                        'options' => [
                            'terms_adresse',
                            'terms_codepostal',
                            'terms_ville',
                            'terms_paysresidence',
                            'terms_datedenaissance',
                            'terms_genre',
                            'filiere_user',
                            'terms_formation_niveau',
                            'langue_cible',
                        ],
                    ],
                ],
                [
                    'variable' => 'allow_fields_inscription',
                    'json_example' => [
                        'fields' => [
                            'lastname',
                            'firstname',
                            'email',
                            'language',
                            'phone',
                            'address',
                        ],
                        'extra_fields' => [
                            'terms_nationalite',
                            'terms_numeroderue',
                            'terms_nomderue',
                            'terms_codepostal',
                            'terms_paysresidence',
                            'terms_ville',
                            'terms_datedenaissance',
                            'terms_genre',
                            'filiere_user',
                            'terms_formation_niveau',
                            'terms_villedustage',
                            'terms_adresse',
                            'gdpr',
                            'langue_cible',
                        ],
                    ],
                ],
                [
                    'variable' => 'redirect_after_login',
                    'json_example' => [
                        'COURSEMANAGER' => 'courses',
                        'STUDENT' => 'courses',
                        'DRH' => '',
                        'SESSIONADMIN' => 'admin-dashboard',
                        'STUDENT_BOSS' => 'main/my_space/student.php',
                        'INVITEE' => 'courses',
                        'ADMIN' => 'admin',
                    ],
                ],
            ],
            'security' => [
                [
                    'variable' => 'proxy_settings',
                    'json_example' => [
                        'stream_context_create' => [
                            'http' => [
                                'proxy' => 'tcp://example.com:8080',
                                'request_fulluri' => true,
                            ],
                        ],
                        'curl_setopt_array' => [
                            'CURLOPT_PROXY' => 'http://example.com',
                            'CURLOPT_PROXYPORT' => '8080',
                        ],
                    ],
                ],
                [
                    'variable' => 'password_requirements',
                    'json_example' => [
                        'min' => [
                            'lowercase' => 2,
                            'uppercase' => 2,
                            'numeric' => 2,
                            'length' => 8,
                        ],
                    ],
                ],
                [
                    'variable' => 'allow_online_users_by_status',
                    'json_example' => [
                        'status' => [1, 5],
                    ],
                ],
            ],
            'session' => [
                [
                    'variable' => 'my_courses_session_order',
                    'json_example' => [
                        'field' => 'end_date',
                        'order' => 'desc',
                    ],
                ],
                [
                    'variable' => 'session_import_settings',
                    'json_example' => [
                        'options' => [
                            'session_exists_default_option' => '1',
                            'send_mail_default_option' => '1',
                        ],
                    ],
                ],
                [
                    'variable' => 'tracking_columns',
                    'json_example' => [
                        'course_session' => [
                            'course_title' => true,
                            'published_exercises' => true,
                            'new_exercises' => true,
                            'my_average' => true,
                            'average_exercise_result' => true,
                            'time_spent' => true,
                            'lp_progress' => true,
                            'score' => true,
                            'best_score' => true,
                            'last_connection' => true,
                            'details' => true,
                        ],
                        'my_students_lp' => [
                            'lp' => true,
                            'time' => true,
                            'best_score' => true,
                            'latest_attempt_avg_score' => true,
                            'progress' => true,
                            'last_connection' => true,
                        ],
                        'my_progress_lp' => [
                            'lp' => true,
                            'time' => true,
                            'progress' => true,
                            'score' => true,
                            'best_score' => true,
                            'last_connection' => true,
                        ],
                        'my_progress_courses' => [
                            'course_title' => true,
                            'time_spent' => true,
                            'progress' => true,
                            'best_score_in_lp' => true,
                            'best_score_not_in_lp' => true,
                            'latest_login' => true,
                            'details' => true,
                        ],
                    ],
                ],
                [
                    'variable' => 'session_creation_user_course_extra_field_relation_to_prefill',
                    'json_example' => [
                        'fields' => [
                            'client' => 'client',
                            'region' => 'region',
                        ],
                    ],
                ],
                [
                    'variable' => 'session_creation_form_set_extra_fields_mandatory',
                    'json_example' => [
                        'fields' => ['client', 'region'],
                    ],
                ],
            ],
            'skill' => [
                [
                    'variable' => 'skill_levels_names',
                    'json_example' => [
                        'levels' => [
                            1 => 'Skills',
                            2 => 'Capability',
                            3 => 'Dimension',
                        ],
                    ],
                ],
            ],
            'survey' => [
                [
                    'variable' => 'hide_survey_edition',
                    'json_example' => [
                        'codes' => [],
                    ],
                ],
                [
                    'variable' => 'survey_additional_teacher_modify_actions',
                    'json_example' => [
                        'myplugin' => ['MyPlugin', 'urlGeneratorCallback'],
                    ],
                ],
            ],
            'ticket' => [
                [
                    'variable' => 'ticket_project_user_roles',
                    'json_example' => [
                        'permissions' => [
                            1 => [17, 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $groupedTemplates = self::getTemplatesGrouped();

        $settingsRepo = $manager->getRepository(SettingsCurrent::class);
        $templateRepo = $manager->getRepository(SettingsValueTemplate::class);

        foreach ($groupedTemplates as $category => $templates) {
            foreach ($templates as $data) {
                // Check if the template already exists
                $template = $templateRepo->findOneBy(['variable' => $data['variable']]);

                if (!$template) {
                    $template = new SettingsValueTemplate();
                    $template->setCreatedAt(new DateTime());
                }

                $template
                    ->setVariable($data['variable'])
                    ->setJsonExample(json_encode($data['json_example'], JSON_PRETTY_PRINT))
                    ->setUpdatedAt(new DateTime())
                ;

                $manager->persist($template);
                $manager->flush(); // ensure ID is generated for linking

                // Now update the settings table to link this template
                $settings = $settingsRepo->findBy(['variable' => $data['variable']]);

                foreach ($settings as $setting) {
                    if ($setting->getValueTemplate()?->getId() !== $template->getId()) {
                        $setting->setValueTemplate($template);
                        $manager->persist($setting);
                    }
                }
            }
        }

        $manager->flush();
    }
}
