<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExtraFieldFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $list = [
            [
                'variable' => 'legal_accept',
                'display_text' => 'Legal',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'already_logged_in',
                'display_text' => 'Already logged in',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'update_type',
                'display_text' => 'Update script type',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'tags',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TAG,
            ],
            [
                'variable' => 'rssfeeds',
                'display_text' => 'RSS',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'dashboard',
                'display_text' => 'Dashboard',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'user_chat_status',
                'display_text' => 'User chat status',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'google_calendar_url',
                'display_text' => 'Google Calendar URL',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'captcha_blocked_until_date',
                'display_text' => 'Account locked until',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'special_course',
                'display_text' => 'Special course',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'Tags',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TAG,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'video_url',
                'display_text' => 'VideoUrl',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_VIDEO_URL,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'image',
                'display_text' => 'Image',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_FILE_IMAGE,
                'visible_to_self' => true,
                'changeable' => true,
            ],

            [
                'variable' => 'mail_notify_invitation',
                'display_text' => 'MailNotifyInvitation',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_message',
                'display_text' => 'MailNotifyMessage',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_group_message',
                'display_text' => 'MailNotifyGroupMessage',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'skype',
                'display_text' => 'Skype',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'linkedin_url',
                'display_text' => 'LinkedInUrl',
                'item_type' => ExtraField::USER_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'Tags',
                'item_type' => ExtraField::SKILL_FIELD_TYPE,
                'value_type' => \ExtraField::FIELD_TYPE_TAG,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'send_notification_at_a_specific_date',
                'display_text' => 'Send notification at a specific date',
                'item_type' => ExtraField::COURSE_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_DATE,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'date_to_send_notification',
                'display_text' => 'Date to send notification',
                'item_type' => ExtraField::COURSE_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_DATE,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'send_to_users_in_session',
                'display_text' => 'Send to users in session',
                'item_type' => ExtraField::SESSION_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'session_courses_read_only_mode',
                'display_text' => 'Lock Course In Session',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'is_mandatory',
                'display_text' => 'Is Mandatory',
                'item_type' => ExtraField::SURVEY_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'show_in_catalogue',
                'display_text' => 'Show in Catalogue',
                'item_type' => ExtraField::COURSE_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_RADIO,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'multiple_language',
                'display_text' => 'Multiple Language',
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
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'invisible',
                'display_text' => 'Invisible',
                'item_type' => ExtraField::LP_VIEW_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'start_date',
                'display_text' => 'Start Date',
                'item_type' => ExtraField::LP_ITEM_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'end_date',
                'display_text' => 'End Date',
                'item_type' => ExtraField::LP_ITEM_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_DATETIME,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'attachment',
                'display_text' => 'Attachment',
                'item_type' => ExtraField::SCHEDULED_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_FILE,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'send_to_coaches',
                'display_text' => 'Send to Coaches',
                'item_type' => ExtraField::SCHEDULED_ANNOUNCEMENT,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'work_time',
                'display_text' => 'Considered working time',
                'item_type' => ExtraField::WORK_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
                'visible_to_self' => true,
                'changeable' => true,
            ],
        ];

        $options = [
            'At once',
            'Daily',
            'No',
        ];

        foreach ($list as $data) {
            $extraField = (new ExtraField())
                ->setVariable($data['variable'])
                ->setDisplayText($data['display_text'])
                ->setItemType($data['item_type'])
                ->setValueType($data['value_type'])
                ->setChangeable($data['changeable'] ?? false)
                ->setVisibleToSelf($data['visible_to_self'] ?? false)
            ;

            if (isset($data['default_value'])) {
                $extraField->setDefaultValue((string) $data['default_value']);
            }

            if (isset($data['add_options'])) {
                foreach ($options as $key => $text) {
                    $extraFieldOption = (new ExtraFieldOptions())
                        ->setField($extraField)
                        ->setDisplayText($text)
                        ->setOptionOrder($key + 1)
                    ;
                    $manager->persist($extraFieldOption);
                }
            }
            $manager->persist($extraField);
        }

        $manager->flush();
    }
}
