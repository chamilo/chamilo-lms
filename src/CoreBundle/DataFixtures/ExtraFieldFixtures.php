<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ExtraFieldFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['extrafield-update'];
    }

    public static function getExtraFields(): array
    {
        return [
            [
                'variable' => 'legal_accept',
                'display_text' => 'Legal',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'already_logged_in',
                'display_text' => 'Already logged in',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'update_type',
                'display_text' => 'Update script type',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'tags',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TAG,
            ],
            [
                'variable' => 'rssfeeds',
                'display_text' => 'RSS',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'dashboard',
                'display_text' => 'Dashboard',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'user_chat_status',
                'display_text' => 'User chat status',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'google_calendar_url',
                'display_text' => 'Google Calendar URL',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'captcha_blocked_until_date',
                'display_text' => 'Account locked until',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'Tags',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TAG,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'video_url',
                'display_text' => 'VideoUrl',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'image',
                'display_text' => 'Image',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
                'visible_to_self' => true,
                'changeable' => true,
            ],

            [
                'variable' => 'mail_notify_invitation',
                'display_text' => 'Notify of invitations by email',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_message',
                'display_text' => 'Notify of messages by email',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_group_message',
                'display_text' => 'Notify of group messages by email',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'skype',
                'display_text' => 'Skype',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'linkedin_url',
                'display_text' => 'LinkedInUrl',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'send_notification_at_a_specific_date',
                'display_text' => 'Send notification at a specific date',
                'item_type' => ExtraField::COURSE_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_DATE,
            ],
            [
                'variable' => 'date_to_send_notification',
                'display_text' => 'Date to send notification',
                'item_type' => ExtraField::COURSE_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_DATE,
            ],
            [
                'variable' => 'send_to_users_in_session',
                'display_text' => 'Send to users in session',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'session_courses_read_only_mode',
                'display_text' => 'Lock course in session',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'is_mandatory',
                'display_text' => 'Is mandatory',
                'item_type' => ExtraField::SURVEY_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'show_in_catalogue',
                'display_text' => 'Show in catalogue',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_RADIO,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'multiple_language',
                'display_text' => 'In multiple languages',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'acquisition',
                'display_text' => 'Acquisition',
                'item_type' => ExtraField::LP_VIEW_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_RADIO,
            ],
            [
                'variable' => 'invisible',
                'display_text' => 'Invisible',
                'item_type' => ExtraField::LP_VIEW_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'start_date',
                'display_text' => 'Start date',
                'item_type' => ExtraField::LP_ITEM_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
            ],
            [
                'variable' => 'end_date',
                'display_text' => 'End date',
                'item_type' => ExtraField::LP_ITEM_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
            ],
            [
                'variable' => 'attachment',
                'display_text' => 'Attachment',
                'item_type' => ExtraField::SCHEDULED_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_FILE,
            ],
            [
                'variable' => 'send_to_coaches',
                'display_text' => 'Send to coaches',
                'item_type' => ExtraField::SCHEDULED_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'work_time',
                'display_text' => 'Considered working time',
                'item_type' => ExtraField::WORK_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
            ],
            [
                'variable' => 'address',
                'display_text' => 'User address',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'advancedcourselist',
                'display_text' => 'Advanced courses list',
                'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ],
            [
                'variable' => 'ask_for_revision',
                'display_text' => 'Ask for revision',
                'item_type' => ExtraField::FORUM_POST_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'ask_new_password',
                'display_text' => 'Ask for new password',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'authenticationDate',
                'display_text' => 'Authentication date',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
            ],
            [
                'variable' => 'authenticationMethod',
                'display_text' => 'Authentication method',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'azure_id',
                'display_text' => 'Azure ID',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'birthday',
                'display_text' => 'Birthday',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATE,
            ],
            [
                'variable' => 'block_category',
                'display_text' => 'Block Category',
                'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'buycourses_company',
                'display_text' => 'Buyer\'s company',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'buycourses_vat',
                'display_text' => 'Buyer\'s VAT/Tax ID',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'buycourses_address',
                'display_text' => 'Buyer\'s address',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'cas_user',
                'display_text' => 'CAS user',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'careerid',
                'display_text' => 'Career ID',
                'item_type' => ExtraField::CAREER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
            ],
            [
                'variable' => 'career_urls',
                'display_text' => 'Career URLs',
                'item_type' => ExtraField::CAREER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'career_diagram',
                'display_text' => 'Career diagram',
                'item_type' => ExtraField::CAREER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'collapsed',
                'display_text' => 'Collapsed',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'created_by',
                'display_text' => 'Created by',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'credentialType',
                'display_text' => 'Credentials type',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'differentiation',
                'display_text' => 'Differentiation',
                'item_type' => ExtraField::QUESTION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'disable_emails',
                'display_text' => 'Disable all emails',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'disable_import_calendar',
                'display_text' => 'Disable import calendar',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'downloaded_at',
                'display_text' => 'Downloaded at',
                'item_type' => ExtraField::USER_CERTIFICATE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
            ],
            [
                'variable' => 'drupal_user_id',
                'display_text' => 'Drupal user ID',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'state',
                'display_text' => 'State',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'end_pause_date',
                'display_text' => 'End pause date',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
            ],
            [
                'variable' => 'gdpr',
                'display_text' => 'GDPR compliance',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'isFromNewLogin',
                'display_text' => 'Is from new login',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'language',
                'display_text' => 'Language',
                'item_type' => ExtraField::FORUM_CATEGORY_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'longTermAuthenticationRequestTokenUsed',
                'display_text' => 'Long term authentication request token used',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'moodle_password',
                'display_text' => 'Moodle password',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'my_terms',
                'display_text' => 'My terms',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'new_tracking_system',
                'display_text' => 'Use alternate tracking system',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'level',
                'display_text' => 'Level',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'no_automatic_validation',
                'display_text' => 'Skip automatic validation',
                'item_type' => ExtraField::LP_ITEM_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'notification_event',
                'display_text' => 'Event notifications',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'notifications',
                'display_text' => 'Notifications',
                'item_type' => ExtraField::EXERCISE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'number_of_days_for_completion',
                'display_text' => 'Number of days for completion',
                'item_type' => ExtraField::LP_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'oauth2_id',
                'display_text' => 'OAuth2 ID',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'office_address',
                'display_text' => 'Office address',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'office_phone_extension',
                'display_text' => 'Office phone extension',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'organisationemail',
                'display_text' => 'Organisational email',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'pause_formation',
                'display_text' => 'Pause training',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'popular_courses',
                'display_text' => 'Popular courses',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'quality',
                'display_text' => 'Quality',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'remedialcourselist',
                'display_text' => 'Remedial courses list',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'request_for_delete_account',
                'display_text' => 'Request account deletion',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'request_for_delete_account_justification',
                'display_text' => 'Justification for account deletion',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
            ],
            [
                'variable' => 'request_for_legal_agreement_consent_removal',
                'display_text' => 'Request for legal agreement\'s consent removal',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'request_for_legal_agreement_consent_removal_justification',
                'display_text' => 'Justification for consent removal',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
            ],
            [
                'variable' => 'revision_language',
                'display_text' => 'Revision Language',
                'item_type' => ExtraField::FORUM_POST_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'session_career',
                'display_text' => 'Session career link',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
            ],
            [
                'variable' => 'successful_AuthenticationHandlers',
                'display_text' => 'Successful authentication handlers',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'termactivated',
                'display_text' => 'Terms enabled',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => false,
                'changeable' => true,
            ],
            [
                'variable' => 'terms_villedustage',
                'display_text' => 'City of internship\'s terms',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],

            [
                'variable' => 'timezone',
                'display_text' => 'Timezone',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'uid',
                'display_text' => 'UID',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => false,
                'changeable' => true,
            ],
            [
                'variable' => 'use_score_as_progress',
                'display_text' => 'Use score as progress',
                'item_type' => ExtraField::LP_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            ],
            [
                'variable' => 'terms_ville',
                'display_text' => 'City\'s terms',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'time',
                'display_text' => 'Time',
                'item_type' => ExtraField::QUESTION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
            ],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $list = self::getExtraFields();

        foreach ($list as $data) {
            $extraField = $manager->getRepository(ExtraField::class)->findOneBy([
                'variable' => $data['variable'],
                'itemType' => $data['item_type'],
            ]);

            if (!$extraField) {
                $extraField = new ExtraField();
            }

            $extraField->setVariable($data['variable'])
                ->setDisplayText($data['display_text'])
                ->setItemType($data['item_type'])
                ->setValueType($data['value_type'])
                ->setChangeable($data['changeable'] ?? false)
                ->setVisibleToSelf($data['visible_to_self'] ?? false)
                ->setVisibleToOthers($data['visible_to_others'] ?? false)
                ->setFilter($data['filter'] ?? false)
            ;

            if (isset($data['default_value'])) {
                $extraField->setDefaultValue((string) $data['default_value']);
            }

            if (isset($data['add_options']) && $data['add_options']) {
                $options = ['At once', 'Daily', 'No'];
                foreach ($options as $option) {
                    $extraFieldOption = new ExtraFieldOptions();
                    $extraFieldOption->setField($extraField)
                        ->setDisplayText($option)
                        ->setOptionOrder(array_search($option, $options) + 1)
                    ;

                    $manager->persist($extraFieldOption);
                }
            }

            $manager->persist($extraField);
        }

        $manager->flush();
    }
}
