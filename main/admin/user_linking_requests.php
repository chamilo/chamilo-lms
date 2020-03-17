<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\UserBundle\Entity\User;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$hrmId = isset($_GET['hrm']) ? intval($_GET['hrm']) : 0;
$assignedId = isset($_GET['u']) ? intval($_GET['u']) : 0;
$hrm = $hrmId ? api_get_user_entity($hrmId) : null;

$em = Database::getManager();

if (!empty($action) && $hrm && $assignedId) {
    switch ($action) {
        case 'accept':
            /** @var UserRelUser $request */
            $request = $em->getRepository('ChamiloCoreBundle:UserRelUser')
                ->findOneBy([
                    'userId' => $assignedId,
                    'friendUserId' => $hrm->getId(),
                    'relationType' => USER_RELATION_TYPE_HRM_REQUEST,
                ]);

            if ($request) {
                $request->setRelationType(USER_RELATION_TYPE_RRHH);
                $request->setLastEdit(api_get_utc_datetime(null, null, true));
                $em->persist($request);
                $em->flush();

                Display::addFlash(
                    Display::return_message(get_lang('UserLinkingRequestAccepted'), 'success')
                );
            }

            header('Location: '.api_get_self().'?hrm='.$hrm->getId());
            exit;
        case 'reject':
            /** @var UserRelUser $request */
            $request = $em->getRepository('ChamiloCoreBundle:UserRelUser')
                ->findOneBy([
                    'userId' => $assignedId,
                    'friendUserId' => $hrm->getId(),
                    'relationType' => USER_RELATION_TYPE_HRM_REQUEST,
                ]);

            if ($request) {
                $em->remove($request);
                $em->flush();

                Display::addFlash(
                    Display::return_message(get_lang('UserLinkingRequestRejected'), 'success')
                );
            }
            /** Todo: notify the HRM that the request was rejected */
            header('Location: '.api_get_self().'?hrm='.$hrm->getId());
            exit;
        case 'remove':
            /** @var UserRelUser $request */
            $request = $em->getRepository('ChamiloCoreBundle:UserRelUser')
                ->findOneBy([
                    'userId' => $assignedId,
                    'friendUserId' => $hrm->getId(),
                    'relationType' => USER_RELATION_TYPE_RRHH,
                ]);

            if ($request) {
                $em->remove($request);
                $em->flush();

                Display::addFlash(
                    Display::return_message(get_lang('UserLinkRemoved'), 'success')
                );
            }
            /** Todo: notify the HRM that the request was rejected */
            header('Location: '.api_get_self().'?hrm='.$hrm->getId());
            exit;
    }
}

/**
 * Get the data to fill the tables on screen.
 *
 * @param int $status
 *
 * @return array
 */
function getData(User $hrm, $status = HRM_REQUEST)
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
        $status
    );

    $result = [];

    $iconAccept = Display::return_icon('accept.png', get_lang('Accept'));
    $urlAccept = api_get_self().'?action=accept&hrm='.$hrm->getId().'&u=';
    $iconReject = Display::return_icon('delete.png', get_lang('Reject'));
    $urlReject = api_get_self().'?action=reject&hrm='.$hrm->getId().'&u=';
    $iconRemove = Display::return_icon('delete.png', get_lang('Remove'));
    $urlRemove = api_get_self().'?action=remove&hrm='.$hrm->getId().'&u=';

    foreach ($requests as $request) {
        $line = [];
        $studentLink = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$request['user_id'];
        $line[] = '<a href="'.$studentLink.'">'.api_get_person_name($request['firstname'], $request['lastname']).'</a>';
        if ($status == HRM_REQUEST) {
            $line[] = Display::url(
                    $iconAccept,
                    $urlAccept.$request['user_id']
                ).
                Display::url(
                    $iconReject,
                    $urlReject.$request['user_id']
                );
        } else {
            $line[] = Display::url(
                $iconRemove,
                $urlRemove.$request['user_id']
            );
        }
        $result[] = $line;
    }

    return $result;
}

$form = new FormValidator('user_linking_requests', 'get');
$form->addSelectAjax(
    'hrm',
    get_lang('DRH'),
    $hrm ? [$hrm->getId() => UserManager::formatUserFullName($hrm)] : [],
    ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role']
);
$form->addButtonFilter(get_lang('Filter'));

$content = $form->returnForm();

if ($hrm) {
    $requests = getData($hrm);

    if ($requests) {
        $content .= Display::table(
            [get_lang('UserLinkingRequests'), get_lang('Actions')],
            $requests
        );
    } else {
        $content .= Display::table(
            [get_lang('UserLinkingRequests')],
            [get_lang('NoResults')]
        );
    }

    $approved = getData($hrm, DRH);
    if ($approved) {
        $content .= Display::table(
            [get_lang('UserLinkingTo'), get_lang('Actions')],
            $approved
        );
    } else {
        $content .= Display::table(
            [get_lang('UserLinkingTo')],
            [get_lang('NoResults')]
        );
    }
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$toolName = get_lang('UserLinkingRequests');

$view = new Template($toolName);
$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
