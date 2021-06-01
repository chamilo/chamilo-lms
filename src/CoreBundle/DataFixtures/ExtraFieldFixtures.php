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
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'already_logged_in',
                'display_text' => 'Already logged in',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'update_type',
                'display_text' => 'Update script type',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'tags',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TAG,
            ],
            [
                'variable' => 'rssfeeds',
                'display_text' => 'RSS',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'dashboard',
                'display_text' => 'Dashboard',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            /*[
                'variable' => 'timezone',
                'display_text' => 'Timezone',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],*/
            [
                'variable' => 'user_chat_status',
                'display_text' => 'User chat status',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'google_calendar_url',
                'display_text' => 'Google Calendar URL',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'captcha_blocked_until_date',
                'display_text' => 'Account locked until',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
            ],
            [
                'variable' => 'special_course',
                'display_text' => 'Special course',
                'extra_field_type' => ExtraField::COURSE_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_CHECKBOX,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'Tags',
                'extra_field_type' => ExtraField::COURSE_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TAG,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'video_url',
                'display_text' => 'VideoUrl',
                'extra_field_type' => ExtraField::COURSE_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_VIDEO_URL,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'image',
                'display_text' => 'Image',
                'extra_field_type' => ExtraField::SESSION_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_FILE_IMAGE,
                'visible_to_self' => true,
                'changeable' => true,
            ],

            [
                'variable' => 'mail_notify_invitation',
                'display_text' => 'MailNotifyInvitation',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_message',
                'display_text' => 'MailNotifyMessage',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'mail_notify_group_message',
                'display_text' => 'MailNotifyGroupMessage',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_SELECT,
                'visible_to_self' => true,
                'default_value' => 1,
                'add_options' => true,
            ],
            [
                'variable' => 'skype',
                'display_text' => 'Skype',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'linkedin_url',
                'display_text' => 'LinkedInUrl',
                'extra_field_type' => ExtraField::USER_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'changeable' => true,
            ],
            [
                'variable' => 'tags',
                'display_text' => 'Tags',
                'extra_field_type' => ExtraField::SKILL_FIELD_TYPE,
                'field_type' => \ExtraField::FIELD_TYPE_TAG,
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
                ->setExtraFieldType($data['extra_field_type'])
                ->setFieldType($data['field_type'])
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
