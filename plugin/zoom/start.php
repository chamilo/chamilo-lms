<?php
/* For license terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

api_protect_course_script(true);

// the section (for the tabs)
$this_section = SECTION_COURSES;

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$tool_name = get_lang('ZoomVideoconferences');
$tpl = new Template($tool_name);

$plugin = ZoomPlugin::create();

if ($plugin->userIsConferenceManager()) {
    // user can create a new meeting

    // one form to fast and easily create and start an instant meeting
    $createInstantMeetingForm = new FormValidator('createInstantMeetingForm', 'post', '', '_blank');
    $createInstantMeetingForm->addButton('startButton', get_lang('StartInstantMeeting'));
    $tpl->assign('createInstantMeetingForm', $createInstantMeetingForm->returnForm());

    // instant meeting creation
    if ($createInstantMeetingForm->validate()) {
        try {
            $newInstantMeeting = $plugin->createInstantMeeting();
            header('Location: '.$newInstantMeeting->start_url);
            exit;
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }

    // another form to schedule a meeting
    $scheduleMeetingForm = new FormValidator('scheduleMeetingForm');
    $startTimeDatePicker = $scheduleMeetingForm->addDateTimePicker('start_time', get_lang('StartTime'));
    $scheduleMeetingForm->setRequired($startTimeDatePicker);
    $durationNumeric = $scheduleMeetingForm->addNumeric('duration', get_lang('DurationInMinutes'));
    $scheduleMeetingForm->setRequired($durationNumeric);
    $topicText = $scheduleMeetingForm->addText('topic', get_lang('Topic'), true);
    $agendaTextArea = $scheduleMeetingForm->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
    // $passwordText = $scheduleMeetingForm->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
    $registrationOptions = [
        'RegisterAllCourseUsers' => get_lang('RegisterAllCourseUsers'),
    ];
    $groups = GroupManager::get_groups();
    if (!empty($groups)) {
        $registrationOptions['RegisterTheseGroupMembers'] = get_lang('RegisterTheseGroupMembers');
    }
    $registrationOptions['RegisterNoUser'] = get_lang('RegisterNoUser');
    $userRegistrationRadio = $scheduleMeetingForm->addRadio(
        'userRegistration',
        get_lang('UserRegistration'),
        $registrationOptions
    );
    $groupOptions = [];
    foreach ($groups as $group) {
        $groupOptions[$group['id']] = $group['name'];
    }
    $groupIdsSelect = $scheduleMeetingForm->addSelect(
        'groupIds',
        get_lang('RegisterTheseGroupMembers'),
        $groupOptions
    );
    $groupIdsSelect->setMultiple(true);
    if (!empty($groups)) {
        $jsCode = sprintf(
            <<<EOF
getElementById('%s').parentNode.parentNode.parentNode.style.display = getElementById('%s').checked ? 'block' : 'none'
EOF,
            $groupIdsSelect->getAttribute('id'),
            $userRegistrationRadio->getelements()[1]->getAttribute('id')
        );

        $scheduleMeetingForm->setAttribute('onchange', $jsCode);
    }
    $scheduleMeetingForm->addButtonCreate(get_lang('ScheduleTheMeeting'));

    // meeting scheduling
    if ($scheduleMeetingForm->validate()) {
        try {
            $newMeeting = $plugin->createScheduledMeeting(
                new DateTime($startTimeDatePicker->getValue()),
                $durationNumeric->getValue(),
                $topicText->getValue(),
                $agendaTextArea->getValue(),
                '' // $passwordText->getValue()
            );
            Display::addFlash(
                Display::return_message($plugin->get_lang('NewMeetingCreated'))
            );
            if ('RegisterAllCourseUsers' == $userRegistrationRadio->getValue()) {
                $plugin->addRegistrants($newMeeting, $newMeeting->getCourseAndSessionUsers());
                Display::addFlash(
                    Display::return_message($plugin->get_lang('AllCourseUsersWereRegistered'))
                );
            } elseif ('RegisterTheseGroupMembers' == $userRegistrationRadio->getValue()) {
                $userIds = [];
                foreach ($groupIdsSelect->getValue() as $groupId) {
                    $userIds = array_unique(array_merge($userIds, GroupManager::get_users($groupId)));
                }
                $users = Database::getManager()->getRepository(
                    'ChamiloUserBundle:User'
                )->matching(Criteria::create()->where(Criteria::expr()->in('id', $userIds)))->getValues();
                $plugin->addRegistrants($newMeeting, $users);
            }
            location('meeting_from_start.php?meetingId='.$newMeeting->id);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    } else {
        $scheduleMeetingForm->setDefaults(
            [
                'duration' => 60,
                'topic' => api_get_course_info()['title'],
                'userRegistration' => 'RegisterAllCourseUsers',
            ]
        );
    }
    $tpl->assign('scheduleMeetingForm', $scheduleMeetingForm->returnForm());
}

try {
    $tpl->assign('scheduledMeetings', $plugin->getScheduledMeetings());
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();
