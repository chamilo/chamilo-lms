<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiTutorChatService;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\LanguageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ChatRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Entity\CChatConversation;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CChatConversationRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chat;
use CourseChatUtils;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class ChatController extends AbstractController
{
    use ControllerTrait;
    use CourseControllerTrait;
    use ResourceControllerTrait;

    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper,
    ) {}

    #[Route(path: '/resources/chat/', name: 'chamilo_core_chat_home', options: ['expose' => true])]
    public function index(
        Request $request,
        ManagerRegistry $doctrine,
        SettingsCourseManager $courseSettingsManager
    ): Response {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            throw $this->createAccessDeniedException('User is not authenticated.');
        }

        Event::event_access_tool(TOOL_CHAT);
        Event::registerLog([
            'tool' => TOOL_CHAT,
            'action' => 'start',
            'action_details' => 'start-chat',
        ]);

        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        if (null === $course) {
            throw $this->createNotFoundException('Course context was not found in the request.');
        }

        /** @var CDocumentRepository $docsRepo */
        $docsRepo = $doctrine->getRepository(CDocument::class);
        $docsRepo->ensureChatSystemFolder($course, $session);

        // AI tab enable flag (safe default: off unless enabled).
        $aiEnabled = ('true' === api_get_setting('ai_helpers.tutor_chatbot'));

        $courseSettingsManager->setCourse($course);
        $aiDefaultProvider = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');

        $sessionId = (int) ($session?->getId() ?? 0);
        $group = $this->cidReqHelper->getGroupEntity();
        $groupId = (int) ($group?->getIid() ?? 0);

        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach' => ('true' === api_get_setting('chat.course_chat_restrict_to_coach')),
            'user' => api_get_user_info($me, true),
            'emoji_smile' => '<span>&#128522;</span>',
            'course_url_params' => api_get_cidreq(),
            'course' => $course,
            'session_id' => $sessionId,
            'group_id' => $groupId,
            'chat_parent_node_id' => $course->getResourceNode()->getId(),

            // AI flags for UI (course chat only)
            'ai_enabled' => $aiEnabled,
            'ai_default_provider' => $aiDefaultProvider,
        ]);
    }

    #[Route(path: '/resources/chat/conversations/', name: 'chamilo_core_chat_ajax', options: ['expose' => true])]
    public function ajax(
        Request $request,
        ManagerRegistry $doctrine,
        AiTutorChatService $aiTutorChatService
    ): Response {
        $debug = false;

        $log = static function (string $msg, array $ctx = []) use ($debug): void {
            if (!$debug) {
                return;
            }
            error_log('[ChatController] '.$msg.' | '.json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        };

        if (!api_protect_course_script()) {
            return new JsonResponse(['status' => false, 'error' => 'forbidden'], 403);
        }

        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['status' => false, 'error' => 'unauthorized'], 401);
        }

        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();
        $group = $this->cidReqHelper->getGroupEntity();

        if (null === $course) {
            return new JsonResponse(['status' => false, 'error' => 'course_context_missing'], 400);
        }

        $courseId = (int) ($course->getId() ?? 0);
        $userId = (int) $me;
        $sessionId = (int) ($session?->getId() ?? 0);
        $groupId = (int) ($group?->getIid() ?? 0);

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

        // Read friend once (used by both legacy and AI paths)
        $friend = (int) $request->get('friend', 0);

        // Optional provider for AI tutor
        $aiProvider = trim((string) $request->get('ai_provider', ''));

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

                    $newUsersOnline = $chat->countUsersOnline();
                    $oldUsersOnline = (int) $request->get('users_online', 0);

                    if (AiTutorChatService::FRIEND_AI === $friend) {
                        // AI Tutor conversation (private per user)
                        $historyHtml = $aiTutorChatService->renderHistoryHtml($userId, $course, $session, $aiProvider);
                        $lastId = $aiTutorChatService->getLastMessageId($userId, $course, $aiProvider);

                        $json = [
                            'status' => true,
                            'data' => [
                                // Use last message id as "size" so frontend can detect changes.
                                'oldFileSize' => $lastId,
                                'history' => $historyHtml,
                                'usersOnline' => $newUsersOnline,
                                'userList' => $newUsersOnline !== $oldUsersOnline ? $chat->listUsersOnline() : null,
                                'currentFriend' => $friend,
                            ],
                        ];
                        break;
                    }

                    // Legacy conversations (general + private user)
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
                    $json = ['status' => true, 'data' => ['message' => CourseChatUtils::prepareMessage($msg)]];
                    break;

                case 'reset':
                    if (AiTutorChatService::FRIEND_AI === $friend) {
                        $aiTutorChatService->resetConversation($userId, $course, $session, $aiProvider);

                        $json = [
                            'status' => true,
                            'data' => $aiTutorChatService->renderHistoryHtml($userId, $course, $session, $aiProvider),
                        ];
                        break;
                    }

                    $json = ['status' => true, 'data' => $chat->readMessages(true, $friend)];
                    break;

                case 'write':
                    $msg = (string) $request->get('message', '');

                    if (AiTutorChatService::FRIEND_AI === $friend) {
                        $ok = $aiTutorChatService->handleUserMessage($userId, $course, $session, $aiProvider, $msg);
                        $json = ['status' => $ok, 'data' => ['writed' => $ok]];
                        break;
                    }

                    $ok = $chat->saveMessage($msg, $friend);
                    $json = ['status' => $ok, 'data' => ['writed' => $ok]];
                    break;

                default:
                    $json = ['status' => false, 'error' => 'unknown_action'];
                    break;
            }
        } catch (Throwable $e) {
            $log('ajax error', ['error' => $e->getMessage()]);
            $json = ['status' => false, 'error' => $e->getMessage()];
        }

        return new JsonResponse($json);
    }

    /**
     * ===== GLOBAL CHAT (docked) =====
     * This is the API used by DockedChat.vue.
     */

    #[Route(path: '/account/chat', name: 'chamilo_core_global_chat_home', options: ['expose' => true])]
    public function globalHome(): Response
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            throw $this->createAccessDeniedException('User is not authenticated.');
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return $this->redirectToRoute('homepage');
        }

        // Render a simple container; DockedChat typically lives in the global layout.
        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach' => false,
            'user' => api_get_user_info($me, true),
            'emoji_smile' => '<span>&#128522;</span>',

            // Keep template vars safe even if not used.
            'ai_enabled' => false,
            'ai_default_provider' => 'openai',
        ]);
    }

    #[Route(path: '/account/chat/api/start', name: 'chamilo_core_chat_api_start', options: ['expose' => true], methods: ['GET'])]
    public function globalStart(): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $chat = new Chat();

        // Some legacy methods echo JSON directly; capture that safely.
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
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new Response('', 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new Response('', 403);
        }

        $chat = new Chat();
        $html = $chat->getContacts();

        return new Response((string) $html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route(path: '/account/chat/api/heartbeat', name: 'chamilo_core_chat_api_heartbeat', options: ['expose' => true], methods: ['GET'])]
    public function globalHeartbeat(Request $req): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $mode = (string) $req->query->get('mode', 'min');
        $sinceId = (int) $req->query->get('since_id', 0);
        $peerId = (int) $req->query->get('peer_id', 0);

        // Allow client to ask for presence inside the same heartbeat.
        $presenceRaw = (string) $req->query->get('presence_ids', '');
        $presenceIds = $this->parseIdsFromRaw($presenceRaw);

        // Optional contacts refresh flag.
        $includeContacts = (bool) $req->query->get('include_contacts', false);

        $chat = new Chat();
        $data = [];

        // Tiny/min modes (recommended fast path).
        if ('tiny' === $mode && $peerId > 0) {
            $data = $chat->heartbeatTiny((int) $me, $peerId, $sinceId);
        } elseif ('min' === $mode) {
            $data = $chat->heartbeatMin((int) $me, $sinceId);
        } else {
            // Fallback (legacy full heartbeat).
            ob_start();
            $ret = $chat->heartbeat();
            $echoed = ob_get_clean();

            if ('' !== $echoed) {
                return JsonResponse::fromJsonString($echoed);
            }

            if (\is_string($ret)) {
                return JsonResponse::fromJsonString($ret);
            }

            $data = \is_array($ret) ? $ret : [];
        }

        // Attach presence map when requested.
        if (!empty($presenceIds)) {
            $data['presence'] = $this->buildPresenceMap($presenceIds);
        }

        // Attach contacts HTML only when explicitly requested.
        if ($includeContacts) {
            $html = $chat->getContacts();
            $data['contacts_html'] = \is_string($html) ? $html : '';
        }

        $resp = new JsonResponse($data);
        $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $resp;
    }

    #[Route(
        path: '/account/chat/api/history_since',
        name: 'chamilo_core_chat_api_history_since',
        options: ['expose' => true],
        methods: ['GET']
    )]
    public function globalHistorySince(Request $req): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $peerId = (int) $req->query->get('user_id', 0);
        $sinceId = (int) $req->query->get('since_id', 0);

        if ($peerId <= 0) {
            return new JsonResponse([]);
        }

        $chat = new Chat();
        $items = $chat->getIncomingSince($peerId, (int) $me, $sinceId);

        $resp = new JsonResponse($items);
        $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $resp;
    }

    #[Route(
        path: '/account/chat/api/send',
        name: 'chamilo_core_chat_api_send',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function globalSend(
        Request $req,
        AiTutorChatService $aiTutorChatService,
        LanguageHelper $languageHelper,
        AiProviderFactory $aiProviderFactory,
        ChatRepository $chatRepository,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $to = (int) $req->request->get('to', 0);
        $message = (string) $req->request->get('message', '');

        // AI Tutor path (global chat)
        if (AiTutorChatService::FRIEND_AI === $to) {
            // Server-side guard: AI must not be available during tests.
            if (!empty($_SESSION['is_in_a_test'])) {
                return new JsonResponse(['error' => 'ai_disabled_in_exam'], 403);
            }

            $aiEnabledSetting = ('true' === api_get_setting('ai_helpers.tutor_chatbot'));
            if (!$aiEnabledSetting) {
                return new JsonResponse([
                    'error' => 'ai_disabled',
                    'message' => 'AI tutor is disabled by configuration.',
                ], 403);
            }

            $message = trim($message);
            if ('' === $message) {
                return new JsonResponse(['id' => 0]);
            }

            // Course context is OPTIONAL now.
            $course = $this->resolveCourseFromRequest($req, $doctrine);

            $uiLang = $languageHelper->getInterfaceIso();
            $mode = (null !== $course) ? 'course' : 'global';

            $courseTitle = 'Global';
            $courseLang = $uiLang ?: 'en';

            if (null !== $course) {
                $courseTitle = (string) $course->getTitle();

                $tmpLang = '';
                if (method_exists($course, 'getCourseLanguage')) {
                    $tmpLang = (string) ($course->getCourseLanguage() ?? '');
                }
                if ('' === $tmpLang && method_exists($course, 'getLanguage')) {
                    $tmpLang = (string) ($course->getLanguage() ?? '');
                }
                if ('' !== $tmpLang) {
                    $courseLang = $tmpLang;
                }
            }

            // Store context + strict system prompt in session for the AI layer.
            $ctx = [
                'mode' => $mode,
                'course_id' => (int) ($course?->getId() ?? 0),
                'title' => $courseTitle,
                'lang' => $courseLang ?: 'en',
            ];

            try {
                if ($req->hasSession()) {
                    $req->getSession()->set('ai_tutor_context', $ctx);
                    $req->getSession()->set('ai_tutor_system_prompt', $this->buildAiTutorSystemPrompt($ctx));
                }
            } catch (Throwable) {
                // Best effort: ignore session storage failures.
            }

            // Resolve a valid provider key for "text".
            $requestedProvider = trim((string) $req->request->get('ai_provider', ''));
            $providerKey = $this->resolveTextProviderKey($requestedProvider, $aiProviderFactory);

            if (null === $providerKey) {
                return new JsonResponse([
                    'error' => 'ai_not_configured',
                    'message' => 'No AI provider is configured for text generation.',
                ], 503);
            }

            $chat = new Chat();

            $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $now = $nowUtc->format('Y-m-d H:i:s');
            $nowTs = $nowUtc->getTimestamp();

            // Store the user message as a normal chat row (me -> -1)
            $userSanitized = $chat->sanitize($message);
            $userMsgId = $chatRepository->insertChatRow(
                (int) $me,
                AiTutorChatService::FRIEND_AI,
                $userSanitized,
                1,
                $now
            );

            try {
                if (null !== $course) {
                    // Persist into ai_tutor_* when course context exists
                    $assistantText = $aiTutorChatService->handleUserMessageAndGetAssistantText(
                        (int) $me,
                        $course,
                        null,
                        $providerKey,
                        $message
                    );
                } else {
                    // Global mode: keep current behavior (no ai_tutor_* persistence without course)
                    $assistantText = $aiTutorChatService->generateGlobalAssistantReply(
                        (int) $me,
                        $providerKey,
                        $message,
                        $uiLang
                    );
                }
            } catch (Throwable $e) {
                error_log('[AI][chat] Failed to generate assistant reply: '.$e->getMessage());

                return new JsonResponse([
                    'id' => (int) $userMsgId,
                    'error' => 'ai_failed',
                    'message' => $e->getMessage(),
                ], 503);
            }

            $assistantText = \is_string($assistantText) ? trim($assistantText) : '';
            if ('' === $assistantText) {
                return new JsonResponse([
                    'id' => (int) $userMsgId,
                    'error' => 'ai_empty_reply',
                    'message' => 'AI provider returned an empty reply.',
                ], 503);
            }

            if (str_starts_with($assistantText, 'Error:')) {
                return new JsonResponse([
                    'id' => (int) $userMsgId,
                    'error' => 'ai_failed',
                    'message' => $assistantText,
                ], 503);
            }

            // Store assistant message (-1 -> me) as unread (recd=0)
            $assistantSanitized = $chat->sanitize($assistantText);
            $assistantId = $chatRepository->insertChatRow(
                AiTutorChatService::FRIEND_AI,
                (int) $me,
                $assistantSanitized,
                0,
                (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            );

            return new JsonResponse([
                'id' => (int) $userMsgId,
                'assistant' => [
                    'id' => (int) $assistantId,
                    'message' => \Security::remove_XSS($assistantSanitized),
                    'date' => $nowTs,
                    'recd' => 0,
                    'from_user_info' => [
                        'id' => AiTutorChatService::FRIEND_AI,
                        'user_id' => AiTutorChatService::FRIEND_AI,
                        'complete_name' => 'AI Tutor',
                        'user_is_online_in_chat' => 1,
                        'user_is_online' => 1,
                        'online' => 1,
                        'avatar_small' => '',
                    ],
                    'to_user_info' => api_get_user_info((int) $me, true),
                ],
                'mode' => $mode,
            ]);
        }

        // Legacy path (normal users)
        $chat = new Chat();

        ob_start();
        $ret = $chat->send((int) $me, $to, $message);
        $echoed = ob_get_clean();

        if ('' !== $echoed) {
            $trim = trim($echoed);
            if (ctype_digit($trim)) {
                return new JsonResponse(['id' => (int) $trim]);
            }

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
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
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
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $peerId = (int) $req->query->get('user_id', 0);
        $visible = (int) $req->query->get('visible_messages', 0);

        if ($peerId <= 0) {
            return new JsonResponse([]);
        }

        $chat = new Chat();
        $items = $chat->getPreviousMessages($peerId, (int) $me, $visible);

        if (!empty($items)) {
            sort($items);

            return new JsonResponse($items);
        }

        return new JsonResponse([]);
    }

    #[Route(path: '/account/chat/api/preview', name: 'chamilo_core_chat_api_preview', options: ['expose' => true], methods: ['POST'])]
    public function globalPreview(Request $req): Response
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new Response('', 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new Response('', 403);
        }

        $html = CourseChatUtils::prepareMessage((string) $req->request->get('message', ''));

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route(path: '/account/chat/api/presence', name: 'chamilo_core_chat_api_presence', options: ['expose' => true], methods: ['POST'])]
    public function globalPresence(Request $req): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $raw = (string) $req->request->get('ids', '');
        $ids = $this->parseIdsFromRaw($raw);

        $map = $this->buildPresenceMap($ids);

        return new JsonResponse(['presence' => $map]);
    }

    #[Route(path: '/account/chat/api/ack', name: 'chamilo_core_chat_api_ack', options: ['expose' => true], methods: ['POST'])]
    public function globalAck(Request $req): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $peerId = (int) $req->request->get('peer_id', 0);
        $lastSeenId = (int) $req->request->get('last_seen_id', 0);

        if ($lastSeenId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_params'], 400);
        }

        // AI tutor thread is not a real peer in classic chat ack. No-op.
        if (AiTutorChatService::FRIEND_AI === $peerId) {
            return new JsonResponse(['ok' => true, 'updated' => 0]);
        }

        if ($peerId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_params'], 400);
        }

        $chat = new Chat();

        try {
            $n = $chat->ackReadUpTo($peerId, (int) $me, $lastSeenId);

            return new JsonResponse(['ok' => true, 'updated' => $n]);
        } catch (Throwable $e) {
            error_log('[Chat][ack] Failed to ack messages: '.$e->getMessage());

            return new JsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
    #[Route(
        path: '/account/chat/api/tutor_context',
        name: 'chamilo_core_chat_api_tutor_context',
        options: ['expose' => true],
        methods: ['GET']
    )]
    public function globalTutorContext(
        Request $req,
        AiProviderFactory $aiProviderFactory
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if ('true' !== api_get_setting('chat.allow_global_chat')) {
            return new JsonResponse(['error' => 'disabled'], 403);
        }

        $inTest = !empty($_SESSION['is_in_a_test']);

        // Allow showing AI Tutor globally. Also verify at least one text provider exists.
        $aiEnabledSetting = ('true' === api_get_setting('ai_helpers.tutor_chatbot'));
        $providers = $aiProviderFactory->getProvidersForType('text');
        $hasTextProvider = !empty($providers);

        $enabled = $aiEnabledSetting && $hasTextProvider;

        // Course context depends on cid/cidReq if present.
        $course = $this->cidReqHelper->getDoctrineCourseEntity();

        $coursePayload = null;
        $ctx = [
            'mode' => null,
            'course_id' => 0,
            'title' => 'Global',
            'lang' => 'en',
        ];

        if (null !== $course) {
            $courseLang = '';
            if (method_exists($course, 'getCourseLanguage')) {
                $courseLang = (string) ($course->getCourseLanguage() ?? '');
            }
            if ('' === $courseLang && method_exists($course, 'getLanguage')) {
                $courseLang = (string) ($course->getLanguage() ?? '');
            }
            if ('' === $courseLang) {
                $courseLang = 'en';
            }

            $ctx = [
                'mode' => 'course',
                'course_id' => (int) $course->getId(),
                'title' => (string) $course->getTitle(),
                'lang' => $courseLang,
            ];

            $coursePayload = [
                'id' => $ctx['course_id'],
                'title' => $ctx['title'],
                'language' => $ctx['lang'],
            ];
        } else {
            $ctx['mode'] = 'global';
        }

        // Store context so the AI service can build prompts consistently.
        try {
            if ($req->hasSession()) {
                $req->getSession()->set('ai_tutor_context', $ctx);
                $req->getSession()->set('ai_tutor_system_prompt', $this->buildAiTutorSystemPrompt($ctx));
            }
        } catch (Throwable) {
            // Best effort: ignore session storage failures.
        }

        return new JsonResponse([
            'enabled' => $enabled,
            'in_test' => $inTest,
            'course' => $coursePayload,
            'mode' => $ctx['mode'],
            'reason' => $enabled ? null : (!$aiEnabledSetting ? 'disabled_by_setting' : 'no_text_provider'),
        ]);
    }

    private function buildAiTutorSystemPrompt(array $ctx): string
    {
        $title = (string) ($ctx['title'] ?? 'Global');
        $lang = (string) ($ctx['lang'] ?? 'en');
        $mode = (string) ($ctx['mode'] ?? 'global');

        if ('course' === $mode) {
            return sprintf(
                "You are a digital tutor and mentor. You help the user understand topics related to their course '%s'. ".
                "When greeting OR when the user asks what you are, you MUST mention the course title '%s'. ".
                "The course is in '%s' but you must answer in whatever language the user speaks. ".
                "This is educational use. Content that is not appropriate for minors is not acceptable. ".
                "You are not available during exams.",
                $title,
                $title,
                $lang
            );
        }

        return sprintf(
            "You are a digital tutor and mentor inside Chamilo. You help the user with learning and studying in general. ".
            "You must answer in whatever language the user speaks. ".
            "This is educational use. Content that is not appropriate for minors is not acceptable. ".
            "You are not available during exams."
        );
    }

    /**
     * @param string $raw Raw "ids" input ("1,2,3" or JSON array)
     *
     * @return int[]
     */
    private function parseIdsFromRaw(string $raw): array
    {
        if ('' === $raw) {
            return [];
        }

        $tryJson = json_decode($raw, true);
        if (\is_array($tryJson)) {
            return array_values(array_filter(array_map('intval', $tryJson)));
        }

        return array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $raw) ?: [])));
    }

    /**
     * Compute presence map for a list of user ids (1 = online, 0 = offline).
     *
     * @param int[] $ids
     */
    private function buildPresenceMap(array $ids): array
    {
        $map = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            // AI Tutor is always online.
            if (AiTutorChatService::FRIEND_AI === $id) {
                $map[$id] = 1;
                continue;
            }

            if ($id <= 0) {
                continue;
            }

            // Keep legacy user info lookup to preserve existing "online" behavior.
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
                $ts = strtotime((string) $ui['last_connection']) ?: 0;
                $online = (time() - $ts) <= 120;
            }

            $map[$id] = $online ? 1 : 0;
        }

        // Ensure AI Tutor presence exists when requested ids include it.
        if (in_array(AiTutorChatService::FRIEND_AI, $ids, true)) {
            $map[AiTutorChatService::FRIEND_AI] = 1;
        }

        return $map;
    }

    /**
     * Pick a valid provider key for the "text" service.
     * - If requested provider is valid for "text", use it.
     * - Otherwise fallback to the first available "text" provider (config order).
     */
    private function resolveTextProviderKey(string $requestedProvider, AiProviderFactory $aiProviderFactory): ?string
    {
        $available = $aiProviderFactory->getProvidersForType('text');

        if (empty($available)) {
            return null;
        }

        if ('' !== $requestedProvider && in_array($requestedProvider, $available, true)) {
            return $requestedProvider;
        }

        // Fallback: first provider configured for text in ai_helpers.ai_providers JSON.
        return $available[0] ?? null;
    }

    /**
     * Returns the authenticated user id using UserHelper.
     */
    private function getCurrentUserIdOrNull(): ?int
    {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            return null;
        }

        $id = $user->getId();
        if (null === $id) {
            return null;
        }

        return (int) $id;
    }

    private function resolveCourseFromRequest(Request $req, ManagerRegistry $doctrine): ?Course
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (null !== $course) {
            return $course;
        }

        $cid = (int) (
            $req->query->get('cidReq')
            ?? $req->query->get('cidreq')
            ?? $req->query->get('cid')
            ?? $req->request->get('cidReq')
            ?? $req->request->get('cidreq')
            ?? $req->request->get('cid')
            ?? 0
        );

        if ($cid <= 0) {
            return null;
        }

        return $doctrine->getRepository(Course::class)->find($cid);
    }
}
