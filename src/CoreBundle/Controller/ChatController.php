<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\CourseBundle\Entity\CChatConversation;
use Chamilo\CourseBundle\Repository\CChatConversationRepository;
use CourseChatUtils;
use Doctrine\Persistence\ManagerRegistry;
use Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractResourceController implements CourseControllerInterface
{
    use ControllerTrait;
    use CourseControllerTrait;
    use ResourceControllerTrait;

    #[Route(path: '/resources/chat/', name: 'chat_home', options: ['expose' => true])]
    public function index(Request $request): Response
    {
        Event::event_access_tool(TOOL_CHAT);
        Event::registerLog([
            'tool'           => TOOL_CHAT,
            'action'         => 'start',
            'action_details' => 'start-chat',
        ]);

        $parentNode = $this->getParentResourceNode($request);

        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach'   => ('true' === api_get_setting('chat.course_chat_restrict_to_coach')),
            'user'                => api_get_user_info(),
            'emoji_smile'         => '<span>&#128522;</span>',
            'course_url_params'   => api_get_cidreq(),
            'course'              => api_get_course_entity(),
            'session_id'          => api_get_session_id(),
            'group_id'            => api_get_group_id(),
            'chat_parent_node_id' => $parentNode?->getId() ?? 0,
        ]);
    }

    #[Route(path: '/resources/chat/conversations/', name: 'chat_ajax', options: ['expose' => true])]
    public function ajax(Request $request, ManagerRegistry $doctrine): Response
    {
        $debug = false;
        $log = function (string $msg, array $ctx = []) use ($debug): void {
            if (!$debug) { return; }
            error_log('[ChatController] '.$msg.' | '.json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };

        if (!api_protect_course_script()) {
            $log('protect.failed');
            return new JsonResponse(['status' => false, 'error' => 'forbidden'], 403);
        }

        $courseId  = api_get_course_int_id();
        $userId    = api_get_user_id();
        $sessionId = api_get_session_id();
        $groupId   = api_get_group_id();

        $log('request.start', [
            'cid'   => $courseId,
            'uid'   => $userId,
            'sid'   => $sessionId,
            'gid'   => $groupId,
            'query' => $request->query->all(),
            'post'  => $request->request->all(),
        ]);

        $parentResourceNode = $this->getParentResourceNode($request);
        $log('parent.node', ['id' => $parentResourceNode?->getId()]);

        if (!$parentResourceNode) {
            return new JsonResponse(['status' => false, 'error' => 'parent_node_not_found'], 404);
        }

        /** @var CChatConversationRepository $conversationRepository */
        $conversationRepository = $doctrine->getRepository(CChatConversation::class);

        // Helper: single-file-per-day behavior, append in chronological order
        $chat = new CourseChatUtils(
            $courseId,
            $userId,
            $sessionId,
            $groupId,
            $parentResourceNode,
            $conversationRepository
        );

        $action = (string) $request->get('action', 'track');
        $log('action', ['name' => $action]);

        $json = ['status' => false];

        try {
            switch ($action) {
                case 'chat_logout':
                    Event::registerLog([
                        'tool'           => TOOL_CHAT,
                        'action'         => 'exit',
                        'action_details' => 'exit-chat',
                    ]);
                    $json = ['status' => true];
                    $log('logout.ok');
                    break;

                case 'track':
                    $chat->keepUserAsConnected();
                    $chat->disconnectInactiveUsers();

                    $friend         = (int) $request->get('friend', 0);
                    $newUsersOnline = $chat->countUsersOnline();
                    $oldUsersOnline = (int) $request->get('users_online', 0);

                    $json = [
                        'status' => true,
                        'data'   => [
                            'oldFileSize'   => false, // kept for BC
                            'history'       => $chat->readMessages(false, $friend),
                            'usersOnline'   => $newUsersOnline,
                            'userList'      => $newUsersOnline !== $oldUsersOnline ? $chat->listUsersOnline() : null,
                            'currentFriend' => $friend,
                        ],
                    ];
                    $log('track.ok', [
                        'friend'      => $friend,
                        'usersOnline' => $newUsersOnline,
                        'listChanged' => ($newUsersOnline !== $oldUsersOnline),
                    ]);
                    break;

                case 'preview':
                    // Sanitize + render a message without saving
                    $msg  = (string) $request->get('message', '');
                    $json = [
                        'status' => true,
                        'data'   => ['message' => $chat->prepareMessage($msg)],
                    ];
                    $log('preview.ok', ['len' => strlen($msg)]);
                    break;

                case 'reset':
                    // Clear today’s log for current scope
                    $friend = (int) $request->get('friend', 0);
                    $json   = [
                        'status' => true,
                        'data'   => $chat->readMessages(true, $friend),
                    ];
                    $log('reset.ok', ['friend' => $friend]);
                    break;

                case 'write':
                    // Append to today’s log; create file/node if needed
                    $friend = (int) $request->get('friend', 0);
                    $msg    = (string) $request->get('message', '');
                    $ok     = $chat->saveMessage($msg, $friend);

                    $json = [
                        'status' => $ok,
                        'data'   => ['writed' => $ok], // BC field name
                    ];
                    $log('write.done', ['friend' => $friend, 'len' => strlen($msg), 'ok' => $ok]);
                    break;

                default:
                    $log('action.unknown', ['name' => $action]);
                    $json = ['status' => false, 'error' => 'unknown_action'];
                    break;
            }
        } catch (\Throwable $e) {
            $log('error', ['action' => $action, 'err' => $e->getMessage()]);
            $json = ['status' => false, 'error' => $e->getMessage()];
        } finally {
            $log('response', ['status' => $json['status'] ?? null]);
        }

        return new JsonResponse($json);
    }
}
