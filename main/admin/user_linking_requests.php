<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\UserRelUser,
    Chamilo\UserBundle\Entity\User;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$hrmId = isset($_GET['hrm']) ? intval($_GET['hrm']) : 0;
$assinedId = isset($_GET['u']) ? intval($_GET['u']) : 0;
$hrm = $hrmId ? api_get_user_entity($hrmId) : null;

$em = Database::getManager();

if ($action === 'accept' && $hrm && $assinedId) {
    /** @var UserRelUser $request */
    $request = $em->getRepository('ChamiloCoreBundle:UserRelUser')
        ->findOneBy([
            'userId' => $assinedId,
            'friendUserId' => $hrm->getId(),
            'relationType' => USER_RELATION_TYPE_HRM_REQUEST
        ]);

    if ($request) {
        $request->setRelationType(USER_RELATION_TYPE_RRHH);
        $em->persist($request);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('UserLinkingRequestAccepted'), 'success')
        );
    }

    header('Location: '.api_get_self().'?hrm='.$hrm->getId());
    exit;
}

function getData(User $hrm)
{
    $requests = UserManager::getUsersFollowedByUser(
        $hrm->getId(),
        null,
        false,
        false,
        false,
        null,
        null,
        null,
        null,
        null,
        null,
        HRM_REQUEST
    );

    $result = [];

    $iconAccept = Display::return_icon('accept.png', get_lang('Accept'));
    $urlAccept = api_get_self().'?action=accept&hrm='.$hrm->getId().'&u=';

    foreach ($requests as $request) {
        $result[] = [
            api_get_person_name($request['firstname'], $request['lastname']),
            Display::url(
                $iconAccept,
                $urlAccept.$request['user_id']
            )
        ];
    }

    return $result;
}

$form = new FormValidator('user_linking_requests', 'get');
$form->addSelectAjax(
    'hrm',
    get_lang('DRH'),
    $hrm ? [$hrm->getId() => $hrm->getCompleteName()] : [],
    ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role']
);
$form->addButtonFilter(get_lang('Filter'));

$content = $form->returnForm();

if ($hrm) {
    $requests = getData($hrm);

    if ($requests) {
        $content .= Display::table(
            [get_lang('UserLinkingTo'), get_lang('Actions')],
            $requests
        );
    } else {
        $content .= Display::return_message(get_lang('NoResults'), 'warning');
    }
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$toolName = get_lang('UserLinkingRequests');

$view = new Template($toolName);
$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
