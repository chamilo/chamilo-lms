<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\CourseBundle\Entity\CChatConversation;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CChatConversationRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chat;
use CourseChatUtils;
use Doctrine\Persistence\ManagerRegistry;
use Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ChatController extends AbstractResourceController implements CourseControllerInterface
{
    use ControllerTrait;
    use CourseControllerTrait;
    use ResourceControllerTrait;

    #[Route(path: '/resources/chat/', name: 'chamilo_core_chat_home', options: ['expose' => true])]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        Event::event_access_tool(TOOL_CHAT);
        Event::registerLog([
            'tool' => TOOL_CHAT,
            'action' => 'start',
            'action_details' => 'start-chat',
        ]);

        $course  = api_get_course_entity();
        $session = api_get_session_entity() ?: null;

        /** @var CDocumentRepository $docsRepo */
        $docsRepo = $doctrine->getRepository(CDocument::class);
        $docsRepo->ensureChatSystemFolder($course, $session);

        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach'   => ('true' === api_get_setting('chat.course_chat_restrict_to_coach')),
            'user'                => api_get_user_info(),
            'emoji_smile'         => '<span>&#128522;</span>',
            'course_url_params'   => api_get_cidreq(),
            'course'              => $course,
            'session_id'          => api_get_session_id(),
            'group_id'            => api_get_group_id(),
            'chat_parent_node_id' => $course->getResourceNode()->getId(),
        ]);
    }

    #[Route(path: '/resources/chat/conversations/', name: 'chamilo_core_chat_ajax', options: ['expose' => true])]
    public function ajax(Request $request, ManagerRegistry $doctrine): Response
    {
        $debug = false;
        $log = function (string $msg, array $ctx = []) use ($debug): void {
            if (!$debug) {
                return;
            }
            error_log('[ChatController] '.$msg.' | '.json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };

        if (!api_protect_course_script()) {
            return new JsonResponse(['status' => false, 'error' => 'forbidden'], 403);
        }

        $courseId  = api_get_course_int_id();
        $userId    = api_get_user_id();
        $sessionId = api_get_session_id();
        $groupId   = api_get_group_id();

        $course  = \api_get_course_entity();
        $session = \api_get_session_entity() ?: null;

        /** @var CChatConversationRepository $convRepo */
        $convRepo = $doctrine->getRepository(CChatConversation::class);
        /** @var CDocumentRepository $docsRepo */
        $docsRepo = $doctrine->getRepository(CDocument::class);

        $docsRepo->ensureChatSystemFolder($course, $session);
        $docRoot = $docsRepo->ensureChatSystemFolderUnderCourseRoot($course, $session);

        $chat = new CourseChatUtils(
            $courseId,
            $userId,
            $sessionId,
            $groupId,
            $docRoot,
            $convRepo
        );

        $action = (string) $request->get('action', 'track');
        $json = ['status' => false];

        try {
            switch ($action) {
                case 'chat_logout':
                    Event::registerLog([
                        'tool' => TOOL_CHAT,
                        'action' => 'exit',
                        'action_details' => 'exit-chat',
                    ]);
                    $json = ['status' => true];
                    break;

                case 'track':
                    $chat->keepUserAsConnected();
                    $chat->disconnectInactiveUsers();

                    $friend = (int) $request->get('friend', 0);
                    $newUsersOnline = $chat->countUsersOnline();
                    $oldUsersOnline = (int) $request->get('users_online', 0);

                    $json = [
                        'status' => true,
                        'data' => [
                            'oldFileSize' => false,
                            'history' => $chat->readMessages(false, $friend),
                            'usersOnline' => $newUsersOnline,
                            'userList' => $newUsersOnline !== $oldUsersOnline ? $chat->listUsersOnline() : null,
                            'currentFriend' => $friend,
                        ],
                    ];
                    break;

                case 'preview':
                    $msg = (string) $request->get('message', '');
                    $json = ['status' => true, 'data' => ['message' => \CourseChatUtils::prepareMessage($msg)]];
                    break;

                case 'reset':
                    $friend = (int) $request->get('friend', 0);
                    $json = ['status' => true, 'data' => $chat->readMessages(true, $friend)];
                    break;

                case 'write':
                    $friend = (int) $request->get('friend', 0);
                    $msg = (string) $request->get('message', '');
                    $ok = $chat->saveMessage($msg, $friend);
                    $json = ['status' => $ok, 'data' => ['writed' => $ok]];
                    break;

                default:
                    $json = ['status' => false, 'error' => 'unknown_action'];
                    break;
            }
        } catch (\Throwable $e) {
            $json = ['status' => false, 'error' => $e->getMessage()];
        }

        return new JsonResponse($json);
    }

    #[Route(path: '/account/chat', name: 'chamilo_core_global_chat_home', options: ['expose' => true])]
    public function globalHome(): Response
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('@ChamiloCore/Chat/chat.html.twig', []);
    }

    #[Route(path: '/account/chat/api/start', name: 'chamilo_core_chat_api_start', options: ['expose' => true], methods: ['GET'])]
    public function globalStart(): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $chat = new Chat();

        ob_start();
        $ret = $chat->startSession();
        $echoed = ob_get_clean();

        if ('' !== $echoed) {
            return JsonResponse::fromJsonString($echoed);
        }

        if (\is_string($ret)) {
            return JsonResponse::fromJsonString((string) $ret);
        }

        return new JsonResponse($ret ?? []);
    }

    #[Route(path: '/account/chat/api/contacts', name: 'chamilo_core_chat_api_contacts', options: ['expose' => true], methods: ['POST'])]
    public function globalContacts(): Response
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new Response('', 403);
        }

        $chat = new Chat();
        $html = $chat->getContacts();

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route(path: '/account/chat/api/heartbeat', name: 'chamilo_core_chat_api_heartbeat', options: ['expose' => true], methods: ['GET'])]
    public function globalHeartbeat(): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $chat = new Chat();

        ob_start();
        $ret = $chat->heartbeat();
        $echoed = ob_get_clean();

        if ('' !== $echoed) {
            return JsonResponse::fromJsonString($echoed);
        }

        if (\is_string($ret)) {
            return JsonResponse::fromJsonString($ret);
        }

        return new JsonResponse($ret ?? []);
    }

    #[Route(path: '/account/chat/api/send', name: 'chamilo_core_chat_api_send', options: ['expose' => true], methods: ['POST'])]
    public function globalSend(Request $req): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $to = (int) $req->request->get('to', 0);
        $message = (string) $req->request->get('message', '');
        $chat = new Chat();

        ob_start();
        $ret = $chat->send(api_get_user_id(), $to, $message);
        $echoed = ob_get_clean();

        if ('' !== $echoed) {
            return JsonResponse::fromJsonString($echoed);
        }

        if (\is_string($ret)) {
            return JsonResponse::fromJsonString($ret);
        }

        return new JsonResponse($ret ?? ['id' => 0]);
    }

    #[Route(path: '/account/chat/api/status', name: 'chamilo_core_chat_api_status', options: ['expose' => true], methods: ['POST'])]
    public function globalStatus(Request $req): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $status = (int) $req->request->get('status', 0);

        $chat = new Chat();
        $chat->setUserStatus($status);

        return new JsonResponse(['ok' => true, 'status' => $status]);
    }

    #[Route(path: '/account/chat/api/history', name: 'chamilo_core_chat_api_history', options: ['expose' => true], methods: ['GET'])]
    public function globalHistory(Request $req): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $peerId = (int) $req->query->get('user_id', 0);
        $visible = (int) $req->query->get('visible_messages', 0);

        if (!$peerId) {
            return new JsonResponse([]);
        }

        $chat = new Chat();
        $items = $chat->getPreviousMessages($peerId, api_get_user_id(), $visible);

        if (!empty($items)) {
            sort($items);

            return new JsonResponse($items);
        }

        return new JsonResponse([]);
    }

    #[Route(path: '/account/chat/api/preview', name: 'chamilo_core_chat_api_preview', options: ['expose' => true], methods: ['POST'])]
    public function globalPreview(Request $req): Response
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new Response('', 403);
        }

        $html = CourseChatUtils::prepareMessage((string) $req->request->get('message', ''));

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route(path: '/account/chat/api/presence', name: 'chamilo_core_chat_api_presence', options: ['expose' => true], methods: ['POST'])]
    public function globalPresence(Request $req): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $raw = (string) $req->request->get('ids', '');
        $ids = [];
        if ('' !== $raw) {
            $tryJson = json_decode($raw, true);
            if (\is_array($tryJson)) {
                $ids = array_filter(array_map('intval', $tryJson));
            } else {
                $ids = array_filter(array_map('intval', preg_split('/[,\s]+/', $raw)));
            }
        }

        $map = [];
        foreach ($ids as $id) {
            $ui = api_get_user_info($id, true);
            $v = $ui['user_is_online_in_chat'] ?? $ui['user_is_online'] ?? $ui['online'] ?? null;
            $online = false;
            if (null !== $v) {
                if (\is_string($v)) {
                    $online = 1 === preg_match('/^(1|true|online|on)$/i', $v);
                } else {
                    $online = !empty($v);
                }
            }
            if (false === $online && !empty($ui['last_connection'])) {
                $ts = api_strtotime($ui['last_connection'], 'UTC');
                $online = (time() - $ts) <= 120;
            }
            $map[$id] = $online ? 1 : 0;
        }

        return new JsonResponse(['presence' => $map]);
    }

    #[Route(path: '/account/chat/api/ack', name: 'chamilo_core_chat_api_ack', options: ['expose' => true], methods: ['POST'])]
    public function globalAck(Request $req): JsonResponse
    {
        api_block_anonymous_users();
        if ('true' !== api_get_setting('allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $peerId = (int) $req->request->get('peer_id', 0);
        $lastSeenId = (int) $req->request->get('last_seen_id', 0);
        if ($peerId <= 0 || $lastSeenId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_params'], 400);
        }

        $chat = new Chat();

        try {
            $n = $chat->ackReadUpTo($peerId, api_get_user_id(), $lastSeenId);

            return new JsonResponse(['ok' => true, 'updated' => $n]);
        } catch (Throwable $e) {
            return new JsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
