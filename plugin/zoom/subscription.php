<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\UserBundle\Entity\User;

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

if (empty($_REQUEST['meetingId'])) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();

/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $_REQUEST['meetingId']]);

if (null === $meeting) {
    api_not_allowed(true, $plugin->get_lang('MeetingNotFound'));
}

if (false !== $meeting->isGlobalMeeting()
    || false != $meeting->isCourseMeeting()
    || 'true' !== $plugin->get('enableParticipantRegistration')
    || !$meeting->requiresRegistration()
) {
    api_not_allowed(true);
}

$currentUser = api_get_user_entity(api_get_user_id());
$userRegistrant = $meeting->getRegistrantByUser($currentUser);

if ($meeting->isCourseMeeting()) {
    api_protect_course_script(true);

    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace').' '.$meeting->getGroup()->getName(),
        ];
    }
}

$form = new FormValidator('subscription');
$form->addHidden('meetingId', $meeting->getMeetingId());

if (!empty($userRegistrant)) {
    $form->addButton(
        'unregister',
        $plugin->get_lang('UnregisterMeToConference'),
        'user-times',
        'warning'
    );

    $form->addHtml(
        '<div class="form-group"><div class="col-sm-8 col-sm-offset-2">'
        .Display::url(
            $plugin->get_lang('ViewMeeting'),
            api_get_path(WEB_PLUGIN_PATH).'zoom/join_meeting.php?meetingId='.$meeting->getMeetingId(),
            ['class' => 'btn btn-primary']
        )
        .'</div></div>'
    );
} else {
    $filtered = array_filter(
        $meeting->getRegistrableUsers(),
        function (User $registableUser) use ($currentUser) {
            return $registableUser->getId() === $currentUser->getId();
        }
    );

    if (empty($filtered)) {
        api_not_allowed(true);
    }

    $form->addButton(
        'register',
        $plugin->get_lang('RegisterMeToConference'),
        'user-plus',
        'success'
    );
}

if ($form->validate()) {
    $values = $form->exportValues();

    if (isset($values['unregister'])) {
        $plugin->unregister($meeting, [$userRegistrant]);
    } else {
        $plugin->registerUsers($meeting, [$currentUser]);
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('RegisteredUserListWasUpdated'), 'success')
    );

    api_location('?meetingId='.$meeting->getMeetingId());
} else {
    $form->protect();
}

$view = new Template('');
$view->assign('meeting', $meeting);
$view->assign('frm_register_unregister', $form->returnForm());
$content = $view->fetch('zoom/view/subscription.tpl');

$view->assign('content', $content);
$view->display_one_col_template();
