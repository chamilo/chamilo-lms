<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AdminBlockEvent;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\NotificationContentEvent;
use Chamilo\CoreBundle\Event\NotificationTitleEvent;
use Chamilo\CoreBundle\Event\WSRegistrationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdvancedSubscriptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::NOTIFICATION_CONTENT => 'onNotificationContent',
            Events::NOTIFICATION_TITLE => 'onNotificationTitle',

            Events::WS_REGISTRATION => 'onWSRegistration',

            Events::ADMIN_BLOCK => 'onAdminBlock',
        ];
    }

    public function onNotificationContent(NotificationContentEvent $event): void
    {
        if (!AdvancedSubscriptionPlugin::create()->isEnabled(true)) {
            return;
        }

        $data = $event->getData();

        if (AbstractEvent::TYPE_PRE === $event->getType()) {
            $data['advanced_subscription_pre_content'] = $event->getContent();

            $event->setData($data);
        } elseif (AbstractEvent::TYPE_POST === $event->getType()) {
            if (!empty($event->getContent()) && !empty($data['advanced_subscription_pre_content'])) {
                $content = str_replace(
                    [
                        '<br /><hr>',
                        '<br />',
                        '<br/>',
                    ],
                    '',
                    $data['advanced_subscription_pre_content']
                );

                $event->setContent($content);
            }
        }
    }

    public function onNotificationTitle(NotificationTitleEvent $event): void
    {
        if (!AdvancedSubscriptionPlugin::create()->isEnabled(true)) {
            return;
        }

        $data = $event->getData();

        if (AbstractEvent::TYPE_PRE === $event->getType()) {
            $data['advanced_subscription_pre_title'] = $event->getTitle();

            $event->setData($data);
        } elseif (AbstractEvent::TYPE_POST === $event->getType()
            && !empty($data['advanced_subscription_pre_title'])
        ) {
            $event->setTitle($data['advanced_subscription_pre_title']);
        }
    }

    public function onWSRegistration(WSRegistrationEvent $event): void
    {
        if (!AdvancedSubscriptionPlugin::create()->isEnabled(true)) {
            return;
        }

        if (AbstractEvent::TYPE_POST === $event->getType()) {
            $server = $event->getServer();

            /** WSSessionListInCategory */

            // Output params for sessionBriefList WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBrief',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => ['name' => 'id', 'type' => 'xsd:int'],
                    // session.name
                    'name' => ['name' => 'name', 'type' => 'xsd:string'],
                    // session.short_description
                    'short_description' => ['name' => 'short_description', 'type' => 'xsd:string'],
                    // session.mode
                    'mode' => ['name' => 'mode', 'type' => 'xsd:string'],
                    // session.date_start
                    'date_start' => ['name' => 'date_start', 'type' => 'xsd:string'],
                    // session.date_end
                    'date_end' => ['name' => 'date_end', 'type' => 'xsd:string'],
                    // session.human_text_duration
                    'human_text_duration' => ['name' => 'human_text_duration', 'type' => 'xsd:string'],
                    // session.vacancies
                    'vacancies' => ['name' => 'vacancies', 'type' => 'xsd:string'],
                    // session.schedule
                    'schedule' => ['name' => 'schedule', 'type' => 'xsd:string'],
                ]
            );

            //Output params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionBriefList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    ['ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionBrief[]', ],
                ],
                'tns:sessionBrief'
            );

            // Input params for WSSessionListInCategory
            $server->wsdl->addComplexType(
                'sessionCategoryInput',
                'complexType',
                'struct',
                'all',
                '',
                [
                    'id' => ['name' => 'id', 'type' => 'xsd:string'], // session_category.id
                    'name' => ['name' => 'name', 'type' => 'xsd:string'], // session_category.name
                    'target' => ['name' => 'target', 'type' => 'xsd:string'], // session.target
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ]
            );

            // Input params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetailInput',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // user_field_values.value
                    'user_id' => ['name' => 'user_id', 'type' => 'xsd:int'],
                    // user_field.user_id
                    'user_field' => ['name' => 'user_field', 'type' => 'xsd:string'],
                    // session.id
                    'session_id' => ['name' => 'session_id', 'type' => 'xsd:int'],
                    // user.profile_completes
                    'profile_completed' => ['name' => 'profile_completed', 'type' => 'xsd:float'],
                    // user.is_connected
                    'is_connected' => ['name' => 'is_connected', 'type' => 'xsd:boolean'],
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ]
            );

            // Output params for WSSessionGetDetailsByUser
            $server->wsdl->addComplexType(
                'advsubSessionDetail',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => ['name' => 'id', 'type' => 'xsd:string'],
                    // session.code
                    'code' => ['name' => 'code', 'type' => 'xsd:string'],
                    // session.place
                    'cost' => ['name' => 'cost', 'type' => 'xsd:float'],
                    // session.place
                    'place' => ['name' => 'place', 'type' => 'xsd:string'],
                    // session.allow_visitors
                    'allow_visitors' => ['name' => 'allow_visitors', 'type' => 'xsd:string'],
                    // session.teaching_hours
                    'teaching_hours' => ['name' => 'teaching_hours', 'type' => 'xsd:int'],
                    // session.brochure
                    'brochure' => ['name' => 'brochure', 'type' => 'xsd:string'],
                    // session.banner
                    'banner' => ['name' => 'banner', 'type' => 'xsd:string'],
                    // session.description
                    'description' => ['name' => 'description', 'type' => 'xsd:string'],
                    // status
                    'status' => ['name' => 'status', 'type' => 'xsd:string'],
                    // action_url
                    'action_url' => ['name' => 'action_url', 'type' => 'xsd:string'],
                    // message
                    'message' => ['name' => 'error_message', 'type' => 'xsd:string'],
                ]
            );

            /** WSListSessionsDetailsByCategory */

            // Input params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'listSessionsDetailsByCategory',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session_category.id
                    'id' => ['name' => 'id', 'type' => 'xsd:string'],
                    // session_category.access_url_id
                    'access_url_id' => ['name' => 'access_url_id', 'type' => 'xsd:int'],
                    // session_category.name
                    'category_name' => ['name' => 'category_name', 'type' => 'xsd:string'],
                    // secret key
                    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
                ],
                [],
                'tns:listSessionsDetailsByCategory'
            );

            // Output params for sessionDetailsCourseList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourse',
                'complexType',
                'struct',
                'all',
                '',
                [
                    'course_id' => ['name' => 'course_id', 'type' => 'xsd:int'], // course.id
                    'course_code' => ['name' => 'course_code', 'type' => 'xsd:string'], // course.code
                    'course_title' => ['name' => 'course_title', 'type' => 'xsd:string'], // course.title
                    'coach_username' => ['name' => 'coach_username', 'type' => 'xsd:string'], // user.username
                    'coach_firstname' => ['name' => 'coach_firstname', 'type' => 'xsd:string'], // user.firstname
                    'coach_lastname' => ['name' => 'coach_lastname', 'type' => 'xsd:string'], // user.lastname
                ]
            );

            // Output array for sessionDetails WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsCourseList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    [
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetailsCourse[]',
                    ],
                ],
                'tns:sessionDetailsCourse'
            );

            // Output params for sessionDetailsList WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetails',
                'complexType',
                'struct',
                'all',
                '',
                [
                    // session.id
                    'id' => [
                        'name' => 'id',
                        'type' => 'xsd:int',
                    ],
                    // session.id_coach
                    'coach_id' => [
                        'name' => 'coach_id',
                        'type' => 'xsd:int',
                    ],
                    // session.name
                    'name' => [
                        'name' => 'name',
                        'type' => 'xsd:string',
                    ],
                    // session.nbr_courses
                    'courses_num' => [
                        'name' => 'courses_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nbr_users
                    'users_num' => [
                        'name' => 'users_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nbr_classes
                    'classes_num' => [
                        'name' => 'classes_num',
                        'type' => 'xsd:int',
                    ],
                    // session.date_start
                    'date_start' => [
                        'name' => 'date_start',
                        'type' => 'xsd:string',
                    ],
                    // session.date_end
                    'date_end' => [
                        'name' => 'date_end',
                        'type' => 'xsd:string',
                    ],
                    // session.nb_days_access_before_beginning
                    'access_days_before_num' => [
                        'name' => 'access_days_before_num',
                        'type' => 'xsd:int',
                    ],
                    // session.nb_days_access_after_end
                    'access_days_after_num' => [
                        'name' => 'access_days_after_num',
                        'type' => 'xsd:int',
                    ],
                    // session.session_admin_id
                    'session_admin_id' => [
                        'name' => 'session_admin_id',
                        'type' => 'xsd:int',
                    ],
                    // session.visibility
                    'visibility' => [
                        'name' => 'visibility',
                        'type' => 'xsd:int',
                    ],
                    // session.session_category_id
                    'session_category_id' => [
                        'name' => 'session_category_id',
                        'type' => 'xsd:int',
                    ],
                    // session.promotion_id
                    'promotion_id' => [
                        'name' => 'promotion_id',
                        'type' => 'xsd:int',
                    ],
                    // session.number of registered users validated
                    'validated_user_num' => [
                        'name' => 'validated_user_num',
                        'type' => 'xsd:int',
                    ],
                    // session.number of registered users from waiting queue
                    'waiting_user_num' => [
                        'name' => 'waiting_user_num',
                        'type' => 'xsd:int',
                    ],
                    // extra fields
                    // Array(field_name, field_value)
                    'extra' => [
                        'name' => 'extra',
                        'type' => 'tns:extrasList',
                    ],
                    // course and coaches data
                    // Array(course_id, course_code, course_title, coach_username, coach_firstname, coach_lastname)
                    'course' => [
                        'name' => 'courses',
                        'type' => 'tns:sessionDetailsCourseList',
                    ],
                ]
            );

            // Output params for WSListSessionsDetailsByCategory
            $server->wsdl->addComplexType(
                'sessionDetailsList',
                'complexType',
                'array',
                '',
                'SOAP-ENC:Array',
                [],
                [
                    [
                        'ref' => 'SOAP-ENC:arrayType',
                        'wsdl:arrayType' => 'tns:sessionDetails[]',
                    ],
                ],
                'tns:sessionDetails'
            );

            // Register the method for WSSessionListInCategory
            $server->register(
                'HookAdvancedSubscription..WSSessionListInCategory', // method name
                ['sessionCategoryInput' => 'tns:sessionCategoryInput'], // input parameters
                ['return' => 'tns:sessionBriefList'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionListInCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service checks if user assigned to course' // documentation
            );

            // Register the method for WSSessionGetDetailsByUser
            $server->register(
                'HookAdvancedSubscription..WSSessionGetDetailsByUser', // method name
                ['advsubSessionDetailInput' => 'tns:advsubSessionDetailInput'], // input parameters
                ['return' => 'tns:advsubSessionDetail'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSSessionGetDetailsByUser', // soapaction
                'rpc', // style
                'encoded', // use
                'This service return session details to specific user' // documentation
            );

            // Register the method for WSListSessionsDetailsByCategory
            $server->register(
                'HookAdvancedSubscription..WSListSessionsDetailsByCategory', // method name
                ['name' => 'tns:listSessionsDetailsByCategory'], // input parameters
                ['return' => 'tns:sessionDetailsList'], // output parameters
                'urn:WSRegistration', // namespace
                'urn:WSRegistration#WSListSessionsDetailsByCategory', // soapaction
                'rpc', // style
                'encoded', // use
                'This service returns a list of detailed sessions by a category' // documentation
            );

            $event->setServer($server);
        }
    }

    public function onAdminBlock(AdminBlockEvent $event): void
    {
        $plugin = AdvancedSubscriptionPlugin::create();

        if (!$plugin->isEnabled(true)) {
            return;
        }

        if (AbstractEvent::TYPE_POST === $event->getType()) {
            $item = [
                'url' => '../../plugin/advanced_subscription/src/admin_view.php',
                'label' => $plugin->get_lang('plugin_title'),
            ];

            $event->setItems('sessions', [$item]);
        }
    }
}
