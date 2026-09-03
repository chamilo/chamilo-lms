<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiTutorChatService;
use Chamilo\CoreBundle\Entity\Chat as ChatEntity;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\LanguageHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ChatRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Event;
use Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class ChatController extends AbstractController
{
    use ControllerTrait;
    use CourseControllerTrait;
    use ResourceControllerTrait;

    private const string AI_TUTOR_UNAVAILABLE_SESSION_KEY = 'ai_tutor_temporarily_unavailable_until';
    private const int AI_TUTOR_UNAVAILABLE_COOLDOWN_SECONDS = 60;
    private const string AI_TUTOR_UNAVAILABLE_MESSAGE = 'AI tutor is temporarily unavailable. Please try again later.';
    private const int AI_SELECTED_TEXT_CONTEXT_MAX_CHARS = 12000;
    private const int AI_CURRENT_PATH_MAX_CHARS = 2048;

    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper,
        private readonly SettingsManager $settingsManager,
        private readonly AiFeatureAccessHelper $aiFeatureAccessHelper,
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
        $aiEnabled = $this->aiFeatureAccessHelper->isFeatureEnabledForCourse(
            'tutor_chatbot',
            (int) $course->getId()
        );

        $courseSettingsManager->setCourse($course);
        $aiDefaultProvider = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');

        $sessionId = (int) ($session?->getId() ?? 0);
        $group = $this->cidReqHelper->getGroupEntity();
        $groupId = (int) ($group?->getIid() ?? 0);

        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach' => ('true' === $this->settingsManager->getSetting('chat.course_chat_restrict_to_coach')),
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

        $action = (string) $request->query->get('action', $request->request->get('action', 'track'));
        $json = ['status' => false];

        // Read friend once (used by both legacy and AI paths)
        $friend = (int) $request->query->get('friend', $request->request->get('friend', 0));

        // Optional provider for AI tutor
        $aiProvider = trim((string) $request->query->get('ai_provider', $request->request->get('ai_provider', '')));

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
                    $oldUsersOnline = (int) $request->query->get('users_online', $request->request->get('users_online', 0));

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
                    $msg = (string) $request->query->get('message', $request->request->get('message', ''));
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
                    $msg = (string) $request->query->get('message', $request->request->get('message', ''));

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

    #[Route(path: '/account/chat', name: 'chamilo_core_global_chat_home', options: ['expose' => true])]
    public function globalHome(): Response
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            throw $this->createAccessDeniedException('User is not authenticated.');
        }

        if (!$this->isGlobalChatEnabled()) {
            return $this->redirectToRoute('homepage');
        }

        // Render a simple container; DockedChat typically lives in the global layout.
        return $this->render('@ChamiloCore/Chat/chat.html.twig', [
            'restrict_to_coach' => false,
            'user' => api_get_user_info($me, true),
            'emoji_smile' => '<span>&#128522;</span>',
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

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledJson([
                'me' => '',
                'user_id' => (int) $me,
                'user_status' => 0,
                'sec_token' => '',
                'items' => [],
            ]);
        }

        $chat = new Chat();

        ob_start();
        $ret = $chat->startSession();
        $echoed = ob_get_clean();

        if ('' !== $echoed) {
            return JsonResponse::fromJsonString($echoed);
        }

        return new JsonResponse($ret);
    }

    #[Route(path: '/account/chat/api/contacts', name: 'chamilo_core_chat_api_contacts', options: ['expose' => true], methods: ['POST'])]
    public function globalContacts(): Response
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new Response('', 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledHtml();
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

        $mode = (string) $req->query->get('mode', 'min');
        $sinceId = (int) $req->query->get('since_id', '0');
        $peerId = (int) $req->query->get('peer_id', '0');
        $presenceRaw = (string) $req->query->get('presence_ids', '');
        $presenceIds = $this->parseIdsFromRaw($presenceRaw);
        $includeContacts = (bool) $req->query->get('include_contacts', '');

        if (!$this->isGlobalChatEnabled()) {
            $payload = [
                'error' => 'disabled',
                'last_id' => $sinceId,
                'has_new' => false,
                'unread_by_peer' => [],
            ];

            if (!empty($presenceIds)) {
                $payload['presence'] = [];
            }
            if ($includeContacts) {
                $payload['contacts_html'] = '';
            }

            $resp = new JsonResponse($payload);
            $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

            return $resp;
        }

        $chat = new Chat();
        $data = [];

        if ('tiny' === $mode && $peerId > 0) {
            $data = $chat->heartbeatTiny((int) $me, $peerId, $sinceId);
        } elseif ('min' === $mode) {
            $data = $chat->heartbeatMin((int) $me, $sinceId);
        } else {
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

        if (!empty($presenceIds)) {
            $data['presence'] = $this->buildPresenceMap($presenceIds);
        }

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
    public function globalHistorySince(
        Request $req,
        AiTutorChatService $aiTutorChatService,
        AiProviderFactory $aiProviderFactory,
        SettingsCourseManager $courseSettingsManager,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            return new JsonResponse([]);
        }

        $peerId = (int) $req->query->get('user_id', '0');
        $sinceId = (int) $req->query->get('since_id', '0');

        if (AiTutorChatService::FRIEND_AI === $peerId) {
            $course = $this->resolveCourseFromRequest($req, $doctrine);

            if (null === $course) {
                if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                    return new JsonResponse([]);
                }

                return new JsonResponse(
                    $this->getGlobalAiChatMessagesSince($doctrine, (int) $me, max(0, $sinceId), 80)
                );
            }

            $courseSettingsManager->setCourse($course);
            $courseSettingValue = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');
            if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', (int) $course->getId())) {
                return new JsonResponse([]);
            }

            $requestedProvider = trim((string) $req->query->get('ai_provider', ''));
            $providerKey = $this->resolveTextProviderKey($requestedProvider ?: $courseSettingValue, $aiProviderFactory);
            if (null === $providerKey) {
                return new JsonResponse([]);
            }

            $msgs = $aiTutorChatService->getDockMessagesSince((int) $me, $course, $providerKey, max(0, $sinceId), 80);

            $out = [];
            foreach ($msgs as $m) {
                $role = (string) $m->getRole();
                $fromId = ('user' === $role) ? (int) $me : AiTutorChatService::FRIEND_AI;

                $out[] = [
                    'id' => (int) $m->getId(),
                    'message' => Security::remove_XSS(nl2br(htmlspecialchars((string) $m->getContent(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))),
                    'date' => (int) $m->getCreatedAt()->getTimestamp(),
                    'recd' => 2,
                    'from_user_info' => ('user' === $role)
                        ? api_get_user_info((int) $me, true)
                        : $this->getAiTutorUserInfo(),
                    'to_user_info' => ('user' === $role)
                        ? $this->getAiTutorUserInfo()
                        : api_get_user_info((int) $me, true),
                    'f' => $fromId,
                ];
            }

            return new JsonResponse($out);
        }

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

        if (!$this->isGlobalChatEnabled()) {
            // Return 200 to avoid frontend exceptions; message won't be delivered.
            return $this->globalChatDisabledJson(['id' => 0]);
        }

        $to = (int) $req->request->get('to', 0);
        $message = (string) $req->request->get('message', '');

        // AI Tutor path (global chat)
        if (AiTutorChatService::FRIEND_AI === $to) {
            // Server-side guard: AI must not be available during tests.
            if (!empty($_SESSION['is_in_a_test'])) {
                return new JsonResponse(['error' => 'ai_disabled_in_exam'], 403);
            }

            $message = trim($message);
            if ('' === $message) {
                return new JsonResponse(['id' => 0]);
            }

            $selectedTextContext = $this->normalizeAiSelectedTextContext(
                (string) $req->request->get('selected_text', '')
            );
            $currentPath = $this->normalizeAiCurrentPath((string) $req->request->get('current_path', ''));

            $course = $this->resolveCourseFromRequest($req, $doctrine);
            $mode = null === $course ? 'global' : 'course';

            if (null === $course) {
                if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                    return new JsonResponse([
                        'error' => 'ai_not_enabled_at_platform',
                        'message' => 'AI tutor is not enabled at platform level.',
                    ], 403);
                }
            } elseif (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse(
                'tutor_chatbot',
                (int) $course->getId()
            )) {
                return new JsonResponse([
                    'error' => 'ai_disabled_for_course',
                    'message' => 'AI tutor is disabled for this course.',
                ], 403);
            }

            $uiLang = $languageHelper->getInterfaceIso();
            $contextLanguage = $uiLang ?: 'en';
            $ctx = [
                'mode' => $mode,
                'course_id' => 0,
                'title' => 'Chamilo',
                'lang' => $contextLanguage,
            ];

            if ($course instanceof Course) {
                $courseLanguage = $this->resolveCourseLanguage($course);
                if ('' !== $courseLanguage) {
                    $contextLanguage = $courseLanguage;
                }

                $ctx = [
                    'mode' => 'course',
                    'course_id' => (int) $course->getId(),
                    'title' => (string) $course->getTitle(),
                    'lang' => $contextLanguage ?: 'en',
                ];
            }

            try {
                if ($req->hasSession()) {
                    $req->getSession()->set('ai_tutor_context', $ctx);
                    $req->getSession()->set(
                        'ai_tutor_system_prompt',
                        $aiTutorChatService->buildContextSystemPrompt($course, (string) $ctx['lang'])
                    );
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

            /*
             * Course and global AI Tutor histories must be independent.
             * Course mode is persisted only in ai_tutor_*; the generic chat
             * table is reserved for the platform-global AI Tutor thread.
             */
            if ($course instanceof Course) {
                if ($this->isAiTutorTemporarilyUnavailable($req)) {
                    return $this->buildAiTutorUnavailableResponse(
                        $chat,
                        $chatRepository,
                        (int) $me,
                        0,
                        $nowTs,
                        'course',
                        false
                    );
                }

                $this->releaseSessionLock($req);

                $result = $aiTutorChatService->sendTutorMessageForDockedChat(
                    (int) $me,
                    $course,
                    null,
                    $providerKey,
                    $message,
                    $uiLang,
                    $selectedTextContext,
                    $currentPath
                );

                if ((int) ($result['id'] ?? 0) <= 0) {
                    $this->markAiTutorTemporarilyUnavailable($req);
                    $result['degraded'] = true;
                    $result['temporarily_unavailable'] = true;
                }

                return new JsonResponse($result);
            }

            // Global AI Tutor messages are the only AI messages stored in chat.
            $userSanitized = $chat->sanitize($message);
            $userMsgId = $chatRepository->insertChatRow(
                (int) $me,
                AiTutorChatService::FRIEND_AI,
                $userSanitized,
                1,
                $now
            );

            if ($this->isAiTutorTemporarilyUnavailable($req)) {
                return $this->buildAiTutorUnavailableResponse(
                    $chat,
                    $chatRepository,
                    (int) $me,
                    (int) $userMsgId,
                    $nowTs,
                    'global'
                );
            }

            $this->releaseSessionLock($req);

            try {
                $assistantText = $aiTutorChatService->generateGlobalAssistantReply(
                    (int) $me,
                    $providerKey,
                    $message,
                    $uiLang,
                    $selectedTextContext,
                    $currentPath
                );
            } catch (Throwable $e) {
                error_log('[AI][chat] Failed to generate assistant reply: '.$e->getMessage());
                $this->markAiTutorTemporarilyUnavailable($req);

                return $this->buildAiTutorUnavailableResponse(
                    $chat,
                    $chatRepository,
                    (int) $me,
                    (int) $userMsgId,
                    $nowTs,
                    'global'
                );
            }

            $assistantText = \is_string($assistantText) ? trim($assistantText) : '';
            if ('' === $assistantText) {
                $this->markAiTutorTemporarilyUnavailable($req);

                return $this->buildAiTutorUnavailableResponse(
                    $chat,
                    $chatRepository,
                    (int) $me,
                    (int) $userMsgId,
                    $nowTs,
                    'global'
                );
            }

            if (str_starts_with($assistantText, 'Error:')) {
                error_log('[AI][chat] Provider returned an error string: '.$assistantText);
                $this->markAiTutorTemporarilyUnavailable($req);

                return $this->buildAiTutorUnavailableResponse(
                    $chat,
                    $chatRepository,
                    (int) $me,
                    (int) $userMsgId,
                    $nowTs,
                    'global'
                );
            }

            // Store assistant message (-1 -> me) as unread (recd=0).
            $assistantSanitized = $chat->sanitize($assistantText);
            $assistantId = $chatRepository->insertChatRow(
                AiTutorChatService::FRIEND_AI,
                (int) $me,
                $assistantSanitized,
                1,
                (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            );

            return new JsonResponse([
                'id' => (int) $userMsgId,
                'assistant' => [
                    'id' => (int) $assistantId,
                    'message' => Security::remove_XSS($assistantSanitized),
                    'date' => $nowTs,
                    'recd' => 1,
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
                'mode' => 'global',
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

        return new JsonResponse($ret ?? ['id' => 0]);
    }

    #[Route(
        path: '/account/chat/api/tutor/save',
        name: 'chamilo_core_chat_api_tutor_save',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function globalTutorSave(
        Request $req,
        AiTutorChatService $aiTutorChatService,
        AiProviderFactory $aiProviderFactory,
        SettingsCourseManager $courseSettingsManager,
        ManagerRegistry $doctrine,
        MessageHelper $messageHelper,
        MessageTagRepository $messageTagRepository,
        TranslatorInterface $translator
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledJson(['ok' => false]);
        }

        $currentPath = $this->normalizeAiCurrentPath((string) $req->request->get('current_path', ''));
        $course = $this->resolveCourseFromRequest($req, $doctrine);
        $mode = null === $course ? 'global' : 'course';
        $providerKey = '';
        $conversationId = 0;
        $sessionId = (int) ($req->request->get('sid') ?? $req->query->get('sid') ?? 0);

        if (null === $course) {
            if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                return new JsonResponse(['error' => 'ai_not_enabled_at_platform'], 403);
            }

            $messages = $this->getGlobalAiTutorArchiveMessages($doctrine, (int) $me);
        } else {
            if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', (int) $course->getId())) {
                return new JsonResponse(['error' => 'ai_disabled_for_course'], 403);
            }

            $courseSettingsManager->setCourse($course);
            $courseSettingValue = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');
            $requestedProvider = trim((string) $req->request->get('ai_provider', ''));
            $providerKey = (string) ($this->resolveTextProviderKey(
                $requestedProvider ?: $courseSettingValue,
                $aiProviderFactory
            ) ?? '');

            if ('' === $providerKey) {
                return new JsonResponse(['error' => 'ai_not_configured'], 503);
            }

            $archive = $aiTutorChatService->getConversationArchiveData((int) $me, $course, $providerKey);
            if (null === $archive) {
                return new JsonResponse(['ok' => false, 'error' => 'empty_conversation'], 400);
            }

            $conversationId = (int) $archive['conversation_id'];
            $providerKey = (string) $archive['provider'];
            if ($sessionId <= 0) {
                $sessionId = (int) $archive['session_id'];
            }
            $messages = $archive['messages'];
        }

        if ([] === $messages) {
            return new JsonResponse(['ok' => false, 'error' => 'empty_conversation'], 400);
        }

        $metadata = [
            'source' => 'ai-tutor',
            'mode' => $mode,
            'course_id' => (int) ($course?->getId() ?? 0),
            'session_id' => max(0, $sessionId),
            'conversation_id' => $conversationId,
            'provider' => $providerKey,
            'path' => $currentPath,
        ];

        $subject = $translator->trans('AI Tutor');
        if ($course instanceof Course) {
            $subject .= ' - '.(string) $course->getTitle();
        }

        $content = $this->buildAiTutorArchiveHtml(
            $messages,
            $metadata,
            $translator->trans('You'),
            $translator->trans('AI Tutor')
        );

        $messageId = $messageHelper->sendMessageSimple(
            (int) $me,
            $subject,
            $content,
            (int) $me,
            false,
            false
        );

        if (null === $messageId) {
            return new JsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
        }

        $this->tagAiTutorArchiveMessage(
            $doctrine,
            $messageTagRepository,
            (int) $me,
            (int) $messageId
        );

        return new JsonResponse([
            'ok' => true,
            'message_id' => (int) $messageId,
            'mode' => $mode,
        ]);
    }

    #[Route(
        path: '/account/chat/api/tutor/reset',
        name: 'chamilo_core_chat_api_tutor_reset',
        options: ['expose' => true],
        methods: ['POST']
    )]
    public function globalTutorReset(
        Request $req,
        AiTutorChatService $aiTutorChatService,
        AiProviderFactory $aiProviderFactory,
        SettingsCourseManager $courseSettingsManager,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledJson(['ok' => false]);
        }

        $course = $this->resolveCourseFromRequest($req, $doctrine);
        if (null === $course) {
            if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                return new JsonResponse(['error' => 'ai_not_enabled_at_platform'], 403);
            }

            $deleted = $this->clearGlobalAiChatConversation($doctrine, (int) $me);

            return new JsonResponse(['ok' => true, 'deleted' => $deleted, 'mode' => 'global']);
        }

        $courseSettingsManager->setCourse($course);
        $courseSettingValue = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');
        if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', (int) $course->getId())) {
            return new JsonResponse(['error' => 'ai_disabled_for_course'], 403);
        }

        $requestedProvider = trim((string) $req->request->get('ai_provider', ''));
        $providerKey = $this->resolveTextProviderKey($requestedProvider ?: $courseSettingValue, $aiProviderFactory);

        if (null === $providerKey) {
            return new JsonResponse(['error' => 'ai_not_configured'], 503);
        }

        $aiTutorChatService->resetConversation((int) $me, $course, null, $providerKey);

        return new JsonResponse(['ok' => true, 'mode' => 'course']);
    }

    private function normalizeAiSelectedTextContext(string $text): string
    {
        $text = trim(strip_tags($text));
        if ('' === $text) {
            return '';
        }

        $normalized = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);
        if (null === $normalized) {
            $normalized = preg_replace('/\s+/', ' ', $text) ?? $text;
        }

        $normalized = trim($normalized);
        if ('' === $normalized) {
            return '';
        }

        if (mb_strlen($normalized, 'UTF-8') > self::AI_SELECTED_TEXT_CONTEXT_MAX_CHARS) {
            return mb_substr($normalized, 0, self::AI_SELECTED_TEXT_CONTEXT_MAX_CHARS, 'UTF-8');
        }

        return $normalized;
    }

    private function normalizeAiCurrentPath(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            return '';
        }

        $path = preg_replace('/[\x00-\x1F\x7F]/u', '', $path) ?? '';
        if ('' === $path || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '';
        }

        if (mb_strlen($path, 'UTF-8') > self::AI_CURRENT_PATH_MAX_CHARS) {
            $path = mb_substr($path, 0, self::AI_CURRENT_PATH_MAX_CHARS, 'UTF-8');
        }

        return $path;
    }

    private function resolveCourseLanguage(Course $course): string
    {
        $tmpLang = '';
        if (method_exists($course, 'getCourseLanguage')) {
            $tmpLang = (string) ($course->getCourseLanguage() ?? '');
        }
        if ('' === $tmpLang && method_exists($course, 'getLanguage')) {
            $tmpLang = (string) ($course->getLanguage() ?? '');
        }

        return $tmpLang;
    }

    #[Route(path: '/account/chat/api/status', name: 'chamilo_core_chat_api_status', options: ['expose' => true], methods: ['POST'])]
    public function globalStatus(Request $req): JsonResponse
    {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $status = (int) $req->request->get('status', 0);

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledJson(['ok' => false, 'status' => $status]);
        }

        $chat = new Chat();
        $chat->setUserStatus($status);

        return new JsonResponse(['ok' => true, 'status' => $status]);
    }

    #[Route(path: '/account/chat/api/history', name: 'chamilo_core_chat_api_history', options: ['expose' => true], methods: ['GET'])]
    public function globalHistory(
        Request $req,
        AiTutorChatService $aiTutorChatService,
        AiProviderFactory $aiProviderFactory,
        SettingsCourseManager $courseSettingsManager,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            return new JsonResponse([]);
        }

        $peerId = (int) $req->query->get('user_id', '0');
        $visible = (int) $req->query->get('visible_messages', '0');

        if (AiTutorChatService::FRIEND_AI === $peerId) {
            $course = $this->resolveCourseFromRequest($req, $doctrine);

            if (null === $course) {
                if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                    return new JsonResponse([]);
                }

                return new JsonResponse(
                    $this->getGlobalAiChatMessagesPage($doctrine, (int) $me, max(0, $visible), 20)
                );
            }

            $courseSettingsManager->setCourse($course);
            $courseSettingValue = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');
            if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', (int) $course->getId())) {
                return new JsonResponse([]);
            }

            $requestedProvider = trim((string) $req->query->get('ai_provider', ''));
            $providerKey = $this->resolveTextProviderKey($requestedProvider ?: $courseSettingValue, $aiProviderFactory);
            if (null === $providerKey) {
                return new JsonResponse([]);
            }

            $msgs = $aiTutorChatService->getDockMessagesPage((int) $me, $course, $providerKey, max(0, $visible), 20);

            $out = [];
            foreach ($msgs as $m) {
                $role = (string) $m->getRole();
                $fromId = ('user' === $role) ? (int) $me : AiTutorChatService::FRIEND_AI;

                $out[] = [
                    'id' => (int) $m->getId(),
                    'message' => Security::remove_XSS(nl2br(htmlspecialchars((string) $m->getContent(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))),
                    'date' => (int) $m->getCreatedAt()->getTimestamp(),
                    'recd' => 2,
                    'from_user_info' => ('user' === $role)
                        ? api_get_user_info((int) $me, true)
                        : $this->getAiTutorUserInfo(),
                    'to_user_info' => ('user' === $role)
                        ? $this->getAiTutorUserInfo()
                        : api_get_user_info((int) $me, true),
                    'f' => $fromId,
                ];
            }

            return new JsonResponse($out);
        }

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

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledHtml();
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

        if (!$this->isGlobalChatEnabled()) {
            return $this->globalChatDisabledJson(['presence' => []]);
        }

        $raw = (string) $req->request->get('ids', '');
        $ids = $this->parseIdsFromRaw($raw);

        $map = $this->buildPresenceMap($ids);

        return new JsonResponse(['presence' => $map]);
    }

    #[Route(path: '/account/chat/api/ack', name: 'chamilo_core_chat_api_ack', options: ['expose' => true], methods: ['POST'])]
    public function globalAck(
        Request $req,
        ManagerRegistry $doctrine,
        AiTutorChatService $aiTutorChatService
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        if (!$this->isGlobalChatEnabled()) {
            // Return 200 to avoid frontend exceptions; ack will be ignored.
            return $this->globalChatDisabledJson(['ok' => false]);
        }

        $peerId = (int) $req->request->get('peer_id', 0);
        $lastSeenId = (int) $req->request->get('last_seen_id', 0);

        if ($lastSeenId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_params'], 400);
        }

        if (AiTutorChatService::FRIEND_AI === $peerId) {
            $course = $this->resolveCourseFromRequest($req, $doctrine);
            if (null === $course) {
                if (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
                    return new JsonResponse(['ok' => false, 'error' => 'ai_not_enabled_at_platform'], 403);
                }

                $updated = $this->ackGlobalAiChatMessages($doctrine, (int) $me, $lastSeenId);

                return new JsonResponse(['ok' => true, 'updated' => $updated, 'mode' => 'global']);
            }

            if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('tutor_chatbot', (int) $course->getId())) {
                return new JsonResponse(['ok' => false, 'error' => 'ai_disabled_for_course'], 403);
            }

            $provider = trim((string) $req->request->get('ai_provider', ''));
            $updated = $aiTutorChatService->ackTutorReadUpTo((int) $me, $course, $provider, $lastSeenId);

            return new JsonResponse(['ok' => true, 'updated' => $updated, 'mode' => 'course']);
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
        AiTutorChatService $aiTutorChatService,
        AiProviderFactory $aiProviderFactory,
        LanguageHelper $languageHelper,
        SettingsCourseManager $courseSettingsManager,
        ManagerRegistry $doctrine
    ): JsonResponse {
        $me = $this->getCurrentUserIdOrNull();
        if (null === $me) {
            return new JsonResponse(['error' => 'unauthorized'], 401);
        }

        $inTest = !empty($_SESSION['is_in_a_test']);

        if (!$this->isGlobalChatEnabled()) {
            return new JsonResponse([
                'enabled' => false,
                'in_test' => $inTest,
                'course' => null,
                'mode' => null,
                'provider' => null,
                'reason' => 'global_chat_disabled',
            ]);
        }

        $providers = $aiProviderFactory->getProvidersForType('text');
        $course = $this->resolveCourseFromRequest($req, $doctrine);
        $mode = null === $course ? 'global' : 'course';
        $coursePayload = null;
        $contextLanguage = $languageHelper->getInterfaceIso() ?: $req->getLocale() ?: 'en';
        $courseSettingValue = '';

        if ($course instanceof Course) {
            $courseLanguage = $this->resolveCourseLanguage($course);
            if ('' !== $courseLanguage) {
                $contextLanguage = $courseLanguage;
            }

            $coursePayload = [
                'id' => (int) $course->getId(),
                'title' => (string) $course->getTitle(),
                'language' => $contextLanguage ?: 'en',
            ];

            $courseSettingsManager->setCourse($course);
            $courseSettingValue = (string) $courseSettingsManager->getCourseSettingValue('tutor_chatbot');

            if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse(
                'tutor_chatbot',
                (int) $course->getId()
            )) {
                return new JsonResponse([
                    'enabled' => false,
                    'in_test' => $inTest,
                    'course' => $coursePayload,
                    'mode' => 'course',
                    'provider' => null,
                    'reason' => 'disabled_for_course',
                ]);
            }
        } elseif (!$this->aiFeatureAccessHelper->isFeatureEnabledAtPlatform('tutor_chatbot')) {
            $reason = AiFeatureAccessHelper::MODE_PLUGIN_DEFINED === $this->aiFeatureAccessHelper->getFeatureMode('tutor_chatbot')
                ? 'plugin_defined_requires_course'
                : 'not_enabled_at_platform';

            return new JsonResponse([
                'enabled' => false,
                'in_test' => $inTest,
                'course' => null,
                'mode' => 'global',
                'provider' => null,
                'reason' => $reason,
            ]);
        }

        if ($this->isAiTutorTemporarilyUnavailable($req)) {
            return new JsonResponse([
                'enabled' => false,
                'in_test' => $inTest,
                'course' => $coursePayload,
                'mode' => $mode,
                'provider' => null,
                'reason' => 'temporarily_unavailable',
            ]);
        }

        if (empty($providers)) {
            return new JsonResponse([
                'enabled' => false,
                'in_test' => $inTest,
                'course' => $coursePayload,
                'mode' => $mode,
                'provider' => null,
                'reason' => 'no_text_provider',
            ]);
        }

        if ($inTest) {
            return new JsonResponse([
                'enabled' => false,
                'in_test' => true,
                'course' => $coursePayload,
                'mode' => $mode,
                'provider' => null,
                'reason' => 'disabled_in_exam',
            ]);
        }

        $providerKey = $this->resolveTextProviderKey($courseSettingValue, $aiProviderFactory);
        if (null === $providerKey) {
            $providerKey = $providers[0] ?? null;
        }

        $ctx = [
            'mode' => $mode,
            'course_id' => $course instanceof Course ? (int) $course->getId() : 0,
            'title' => $course instanceof Course ? (string) $course->getTitle() : 'Chamilo',
            'lang' => $contextLanguage ?: 'en',
        ];

        try {
            if ($req->hasSession()) {
                $req->getSession()->set('ai_tutor_context', $ctx);
                $req->getSession()->set(
                    'ai_tutor_system_prompt',
                    $aiTutorChatService->buildContextSystemPrompt($course, (string) $ctx['lang'])
                );
            }
        } catch (Throwable) {
            // Best effort: ignore session storage failures.
        }

        return new JsonResponse([
            'enabled' => true,
            'in_test' => false,
            'course' => $coursePayload,
            'mode' => $mode,
            'provider' => $providerKey,
            'reason' => null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getGlobalAiChatMessagesPage(
        ManagerRegistry $doctrine,
        int $userId,
        int $visible,
        int $pageSize = 20
    ): array {
        $repository = $doctrine->getRepository(ChatEntity::class);
        $total = (int) $repository->createQueryBuilder('chat')
            ->select('COUNT(chat.id)')
            ->andWhere(
                '(chat.fromUser = :userId AND chat.toUser = :aiId) OR '
                .'(chat.fromUser = :aiId AND chat.toUser = :userId)'
            )
            ->setParameter('userId', $userId)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($total <= 0) {
            return [];
        }

        $visible = max(0, $visible);
        $pageSize = max(1, $pageSize);
        $end = max(0, $total - $visible);
        $start = max(0, $end - $pageSize);
        $length = max(0, $end - $start);

        if ($length <= 0) {
            return [];
        }

        /** @var ChatEntity[] $messages */
        $messages = $repository->createQueryBuilder('chat')
            ->andWhere(
                '(chat.fromUser = :userId AND chat.toUser = :aiId) OR '
                .'(chat.fromUser = :aiId AND chat.toUser = :userId)'
            )
            ->setParameter('userId', $userId)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->orderBy('chat.id', 'ASC')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn (ChatEntity $message): array => $this->mapGlobalAiChatMessage($message, $userId),
            $messages
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getGlobalAiChatMessagesSince(
        ManagerRegistry $doctrine,
        int $userId,
        int $sinceId,
        int $limit = 80
    ): array {
        /** @var ChatEntity[] $messages */
        $messages = $doctrine->getRepository(ChatEntity::class)
            ->createQueryBuilder('chat')
            ->andWhere(
                '((chat.fromUser = :userId AND chat.toUser = :aiId) OR '
                .'(chat.fromUser = :aiId AND chat.toUser = :userId))'
            )
            ->andWhere('chat.id > :sinceId')
            ->setParameter('userId', $userId)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->setParameter('sinceId', max(0, $sinceId))
            ->orderBy('chat.id', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn (ChatEntity $message): array => $this->mapGlobalAiChatMessage($message, $userId),
            $messages
        );
    }

    private function ackGlobalAiChatMessages(ManagerRegistry $doctrine, int $userId, int $lastSeenId): int
    {
        $entityManager = $doctrine->getManagerForClass(ChatEntity::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            return 0;
        }

        return (int) $entityManager
            ->createQueryBuilder()
            ->update(ChatEntity::class, 'chat')
            ->set('chat.recd', ':readStatus')
            ->andWhere('chat.fromUser = :aiId')
            ->andWhere('chat.toUser = :userId')
            ->andWhere('chat.id <= :lastSeenId')
            ->andWhere('chat.recd < :readStatus')
            ->setParameter('readStatus', 2)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->setParameter('userId', $userId)
            ->setParameter('lastSeenId', max(0, $lastSeenId))
            ->getQuery()
            ->execute()
        ;
    }

    private function clearGlobalAiChatConversation(ManagerRegistry $doctrine, int $userId): int
    {
        $entityManager = $doctrine->getManagerForClass(ChatEntity::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            return 0;
        }

        return (int) $entityManager
            ->createQueryBuilder()
            ->delete(ChatEntity::class, 'chat')
            ->andWhere(
                '(chat.fromUser = :userId AND chat.toUser = :aiId) OR '
                .'(chat.fromUser = :aiId AND chat.toUser = :userId)'
            )
            ->setParameter('userId', $userId)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapGlobalAiChatMessage(ChatEntity $message, int $userId): array
    {
        $fromId = (int) ($message->getFromUser() ?? 0);
        $toId = (int) ($message->getToUser() ?? 0);
        $fromUserInfo = AiTutorChatService::FRIEND_AI === $fromId
            ? $this->getAiTutorUserInfo()
            : api_get_user_info($userId, true);
        $toUserInfo = AiTutorChatService::FRIEND_AI === $toId
            ? $this->getAiTutorUserInfo()
            : api_get_user_info($userId, true);

        return [
            'id' => (int) $message->getId(),
            'message' => Security::remove_XSS((string) $message->getMessage()),
            'date' => (int) $message->getSent()->getTimestamp(),
            'recd' => (int) $message->getRecd(),
            'from_user_info' => $fromUserInfo,
            'to_user_info' => $toUserInfo,
            'f' => $fromId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getAiTutorUserInfo(): array
    {
        return [
            'id' => AiTutorChatService::FRIEND_AI,
            'user_id' => AiTutorChatService::FRIEND_AI,
            'complete_name' => 'AI Tutor',
            'user_is_online_in_chat' => 1,
            'user_is_online' => 1,
            'online' => 1,
            'avatar_small' => '',
        ];
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
        if (\in_array(AiTutorChatService::FRIEND_AI, $ids, true)) {
            $map[AiTutorChatService::FRIEND_AI] = 1;
        }

        return $map;
    }

    /**
     * @return list<array{role:string,content:string,date:string}>
     */
    private function getGlobalAiTutorArchiveMessages(ManagerRegistry $doctrine, int $userId): array
    {
        /** @var ChatEntity[] $rows */
        $rows = $doctrine->getRepository(ChatEntity::class)
            ->createQueryBuilder('chat')
            ->andWhere(
                '(chat.fromUser = :userId AND chat.toUser = :aiId) OR '
                .'(chat.fromUser = :aiId AND chat.toUser = :userId)'
            )
            ->setParameter('userId', $userId)
            ->setParameter('aiId', AiTutorChatService::FRIEND_AI)
            ->orderBy('chat.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $messages = [];
        foreach ($rows as $row) {
            $role = (int) $row->getFromUser() === $userId ? 'user' : 'assistant';
            $content = (string) $row->getMessage();
            $content = preg_replace('~<br\s*/?>~i', "\n", $content) ?? $content;
            $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content = trim($content);
            if ('' === $content) {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
                'date' => $row->getSent()->format(DATE_ATOM),
            ];
        }

        return $messages;
    }

    /**
     * @param list<array{role:string,content:string,date:string}> $messages
     * @param array{source:string,mode:string,course_id:int,session_id:int,conversation_id:int,provider:string,path:string} $metadata
     */
    private function buildAiTutorArchiveHtml(
        array $messages,
        array $metadata,
        string $youLabel,
        string $aiTutorLabel
    ): string {
        $attr = static fn (string $value): string => htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $html = '<div hidden'
            .' data-chamilo-source="'.$attr($metadata['source']).'"'
            .' data-ai-tutor-mode="'.$attr($metadata['mode']).'"'
            .' data-course-id="'.(int) $metadata['course_id'].'"'
            .' data-session-id="'.(int) $metadata['session_id'].'"'
            .' data-ai-tutor-conversation-id="'.(int) $metadata['conversation_id'].'"'
            .' data-ai-provider="'.$attr($metadata['provider']).'"'
            .' data-ai-tutor-path="'.$attr($metadata['path']).'"'
            .'></div>';

        foreach ($messages as $message) {
            $role = 'user' === ($message['role'] ?? '') ? 'user' : 'assistant';
            $label = 'user' === $role ? $youLabel : $aiTutorLabel;
            $content = htmlspecialchars(
                trim((string) ($message['content'] ?? '')),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            if ('' === $content) {
                continue;
            }

            $html .= '<p><strong>'.$attr($label).'</strong><br>'.nl2br($content).'</p>';
        }

        return $html;
    }

    private function tagAiTutorArchiveMessage(
        ManagerRegistry $doctrine,
        MessageTagRepository $messageTagRepository,
        int $userId,
        int $messageId
    ): void {
        $entityManager = $doctrine->getManagerForClass(Message::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $message = $entityManager->getRepository(Message::class)->find($messageId);
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$message instanceof Message || !$user instanceof User) {
            return;
        }

        $tag = $messageTagRepository->findOneBy([
            'user' => $user,
            'tag' => 'ai-tutor',
        ]);

        if (!$tag instanceof MessageTag) {
            $tag = (new MessageTag())
                ->setUser($user)
                ->setTag('ai-tutor')
            ;
            $messageTagRepository->update($tag);
        }

        foreach ($message->getReceivers() as $relation) {
            if (
                MessageRelUser::TYPE_TO === $relation->getReceiverType()
                && (int) $relation->getReceiver()->getId() === $userId
            ) {
                $relation->addTag($tag);
                $entityManager->persist($relation);
                $entityManager->flush();
                break;
            }
        }
    }

    /**
     * Pick a valid provider key for the "text" service.
     * - If requested provider is valid for "text", use it.
     * - Otherwise fallback to the first available "text" provider (config order).
     */
    private function resolveTextProviderKey(
        string $requestedProvider,
        AiProviderFactory $aiProviderFactory,
        ?string $defaultProvider = null
    ): ?string {
        $available = $aiProviderFactory->getProvidersForType('text');

        if (empty($available)) {
            return null;
        }

        if ('' !== $requestedProvider && \in_array($requestedProvider, $available, true)) {
            return $requestedProvider;
        }

        if (null !== $defaultProvider && '' !== trim($defaultProvider) && \in_array($defaultProvider, $available, true)) {
            return $defaultProvider;
        }

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
            $ref = (string) $req->headers->get('referer', '');
            if ('' !== $ref && preg_match('~/course/(\d+)(/|$)~', $ref, $m)) {
                $cid = (int) ($m[1] ?? 0);
            }
        }

        if ($cid <= 0) {
            return null;
        }

        return $doctrine->getRepository(Course::class)->find($cid);
    }

    /**
     * Global chat enable switch.
     */
    private function isGlobalChatEnabled(): bool
    {
        return 'true' === (string) $this->settingsManager->getSetting('chat.allow_global_chat', true);
    }

    /**
     * Return a normalized JSON response when global chat is disabled.
     * This MUST be 200 to avoid frontend fetch wrappers throwing on non-2xx.
     */
    private function globalChatDisabledJson(array $payload = []): JsonResponse
    {
        return new JsonResponse(array_merge(['error' => 'disabled'], $payload));
    }

    /**
     * Return an empty HTML response when global chat is disabled.
     * This MUST be 200 to avoid frontend fetch wrappers throwing on non-2xx.
     */
    private function globalChatDisabledHtml(): Response
    {
        return new Response('', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function isAiTutorTemporarilyUnavailable(Request $request): bool
    {
        try {
            if (!$request->hasSession()) {
                return false;
            }

            $until = (int) $request->getSession()->get(self::AI_TUTOR_UNAVAILABLE_SESSION_KEY, 0);

            return $until > time();
        } catch (Throwable) {
            return false;
        }
    }

    private function markAiTutorTemporarilyUnavailable(Request $request): void
    {
        try {
            if (!$request->hasSession()) {
                return;
            }

            $request->getSession()->set(
                self::AI_TUTOR_UNAVAILABLE_SESSION_KEY,
                time() + self::AI_TUTOR_UNAVAILABLE_COOLDOWN_SECONDS
            );
        } catch (Throwable) {
            // Ignore session failures.
        }
    }

    private function releaseSessionLock(Request $request): void
    {
        try {
            if (!$request->hasSession()) {
                return;
            }

            $request->getSession()->save();
        } catch (Throwable) {
            // Ignore session failures.
        }
    }

    private function buildAiTutorUnavailableResponse(
        Chat $chat,
        ChatRepository $chatRepository,
        int $userId,
        int $userMessageId,
        int $timestamp,
        string $mode = 'course',
        bool $persistInGlobalChat = true
    ): JsonResponse {
        $assistantSanitized = $chat->sanitize(self::AI_TUTOR_UNAVAILABLE_MESSAGE);
        $assistantId = 0;

        if ($persistInGlobalChat) {
            $assistantId = $chatRepository->insertChatRow(
                AiTutorChatService::FRIEND_AI,
                $userId,
                $assistantSanitized,
                1,
                (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
            );
        }

        return new JsonResponse([
            'id' => $userMessageId,
            'assistant' => [
                'id' => (int) $assistantId,
                'message' => Security::remove_XSS($assistantSanitized),
                'date' => $timestamp,
                'recd' => 1,
                'from_user_info' => [
                    'id' => AiTutorChatService::FRIEND_AI,
                    'user_id' => AiTutorChatService::FRIEND_AI,
                    'complete_name' => 'AI Tutor',
                    'user_is_online_in_chat' => 1,
                    'user_is_online' => 1,
                    'online' => 1,
                    'avatar_small' => '',
                ],
                'to_user_info' => api_get_user_info($userId, true),
            ],
            'mode' => $mode,
            'degraded' => true,
            'temporarily_unavailable' => true,
        ]);
    }
}
