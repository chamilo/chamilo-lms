<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiTutorConversation;
use Chamilo\CoreBundle\Entity\AiTutorMessage;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\AiTutorConversationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class AiTutorChatService
{
    /**
     * Special "friend id" used by the course chat UI to represent the AI Tutor.
     * -  0 => course public chat
     * >  0 => private chat with a user
     * -1 => AI Tutor private conversation (per user).
     */
    public const FRIEND_AI = -1;

    private const DEFAULT_PROVIDER = 'openai';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly EntityManagerInterface $em,
        private readonly AiTutorConversationRepository $conversationRepo,
        private readonly AiChatCompletionClientInterface $client,
        private readonly LoggerInterface $logger
    ) {}

    public function getOrCreateConversation(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider
    ): AiTutorConversation {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null !== $conversation) {
            // Keep session updated when available (optional).
            if (null !== $session && $conversation->getSession()?->getId() !== $session->getId()) {
                $conversation->setSession($session);
                $this->em->flush();
            }

            return $conversation;
        }

        /** @var User $userRef */
        $userRef = $this->em->getReference(User::class, $userId);

        $conversation = (new AiTutorConversation())
            ->setUser($userRef)
            ->setCourse($course)
            ->setSession($session)
            ->setAiProvider($provider)
        ;

        $this->em->persist($conversation);
        $this->em->flush();

        $this->logger->info('[AiTutorChat] Conversation created', [
            'userId' => $userId,
            'courseId' => (int) $course->getId(),
            'provider' => $provider,
            'sessionId' => $session?->getId(),
            'conversationId' => $conversation->getId(),
        ]);

        return $conversation;
    }

    public function resetConversation(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider
    ): void {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            // Nothing to reset.
            return;
        }

        $deleted = $this->conversationRepo->clearMessages($conversation);

        // Reset timestamps + provider conversation id.
        $conversation->setLastMessageAt(null);
        $conversation->setProviderConversationId(null);

        // Keep session updated when available (optional).
        if (null !== $session && $conversation->getSession()?->getId() !== $session->getId()) {
            $conversation->setSession($session);
        }

        $this->em->flush();

        $this->logger->info('[AiTutorChat] Conversation reset', [
            'userId' => $userId,
            'courseId' => (int) $course->getId(),
            'provider' => $provider,
            'deletedMessages' => $deleted,
            'conversationId' => $conversation->getId(),
        ]);
    }

    public function handleUserMessage(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider,
        string $message
    ): bool {
        try {
            $this->handleUserMessageAndGetAssistantText($userId, $course, $session, $provider, $message);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('[AiTutorChat] handleUserMessage failed', [
                'userId' => $userId,
                'courseId' => (int) $course->getId(),
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function renderHistoryHtml(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider
    ): string {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            // Do not create a conversation just for polling; show an empty state.
            return $this->renderEmptyState();
        }

        // Optional: keep session in sync when browsing the history.
        if (null !== $session && $conversation->getSession()?->getId() !== $session->getId()) {
            $conversation->setSession($session);
            $this->em->flush();
        }

        $messages = $this->conversationRepo->findMessages($conversation);

        if (empty($messages)) {
            return $this->renderEmptyState();
        }

        return $this->renderMessages($messages);
    }

    public function getLastMessageId(
        int $userId,
        Course $course,
        string $provider
    ): int {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            return 0;
        }

        return (int) $this->conversationRepo->getLastMessageId($conversation);
    }

    public function generateGlobalAssistantReply(
        int $userId,
        string $providerKey,
        string $message,
        string $uiLang
    ): string {
        $systemPrompt = $this->resolveSystemPrompt($uiLang);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
        ];

        // Use the shared client for consistent behavior (text type + fallbacks).
        return $this->client->chat($providerKey, $messages, [
            'language' => $uiLang,
            'user_id' => $userId,
            'temperature' => 0.4,
        ]);
    }

    private function resolveSystemPrompt(string $uiLang): string
    {
        try {
            $req = $this->requestStack->getCurrentRequest();
            if ($req && $req->hasSession()) {
                $v = (string) $req->getSession()->get('ai_tutor_system_prompt', '');
                if ('' !== trim($v)) {
                    return $v;
                }
            }
        } catch (Throwable) {
            // ignore
        }

        return "You are a digital tutor and mentor inside Chamilo. Answer in the user's language.";
    }

    private function generateAssistantReply(
        string $provider,
        Course $course,
        AiTutorConversation $conversation
    ): string {
        $system = $this->buildSystemPrompt($course);

        // Fetch last N messages.
        $history = $this->conversationRepo->findMessages($conversation, 20);

        $providerMessages = [
            ['role' => 'system', 'content' => $system],
        ];

        foreach ($history as $m) {
            $providerMessages[] = [
                'role' => (string) $m->getRole(),
                'content' => (string) $m->getContent(),
            ];
        }

        // Pass stored provider conversation id when available.
        $opts = [
            'temperature' => 0.4,
        ];

        $prevId = $conversation->getProviderConversationId();
        if (null !== $prevId && '' !== trim($prevId)) {
            $opts['conversation_id'] = $prevId;
        }

        $res = $this->client->chatWithMeta($provider, $providerMessages, $opts);

        // Persist provider conversation id when returned/updated.
        if (null !== $res->conversationId && $res->conversationId !== $conversation->getProviderConversationId()) {
            $conversation->setProviderConversationId($res->conversationId);
            // No explicit flush needed here; handleUserMessage() flushes later when persisting assistant message.
        }

        $text = trim($res->text);
        if ('' === $text) {
            $text = 'I could not generate a response right now. Please try again.';
        }

        return $text;
    }

    private function buildSystemPrompt(Course $course, string $courseLanguage = ''): string
    {
        $title = (string) ($course->getTitle() ?: 'this course');
        $lang = trim($courseLanguage);

        return "You are a digital tutor and mentor. You help me understand topics related to my courses, in this case '{$title}'. "
            .($lang ? "The course is in '{$lang}' but just answer me in whatever language I talk to you. " : 'Just answer me in whatever language I talk to you. ')
            .'This is an educational use, so content that would not be appropriate for children (or minors under any law) is not acceptable. '
            ."You are not available to me when I'm taking an exam, just in case I forget and I ask why you weren't there. "
            .'You must mention the course title when greeting or when the user asks what you are. '
            .'If the user asks something unrelated to the course topic, politely redirect to course-related help.';
    }

    private function renderEmptyState(): string
    {
        return '<div class="py-10 text-center text-sm text-gray-500">'
            .'<div class="mb-2 text-lg">🤖</div>'
            .'<div class="font-medium text-gray-700">AI Tutor</div>'
            .'<div class="mt-1">Ask a question to start the conversation.</div>'
            .'</div>';
    }

    /**
     * @param AiTutorMessage[] $messages
     */
    private function renderMessages(array $messages): string
    {
        $out = '<div class="space-y-3">';

        foreach ($messages as $m) {
            $role = (string) $m->getRole();
            $content = $this->escape((string) $m->getContent());
            $time = $this->formatTime($m->getCreatedAt());

            $isUser = ('user' === $role);

            $wrapCls = $isUser ? 'justify-end' : 'justify-start';
            $bubbleCls = $isUser
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-900';

            $label = $isUser ? 'You' : 'AI Tutor';

            $out .= '<div class="flex '.$wrapCls.'">';
            $out .= '<div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm '.$bubbleCls.'">';
            $out .= '<div class="opacity-80 text-[11px] mb-1">'.$this->escape($label).' · '.$this->escape($time).'</div>';
            $out .= '<div class="whitespace-pre-wrap">'.$content.'</div>';
            $out .= '</div>';
            $out .= '</div>';
        }

        $out .= '</div>';

        return $out;
    }

    private function formatTime(DateTime $dt): string
    {
        return $dt->format('Y-m-d H:i');
    }

    private function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = trim($provider);

        if ('' === $provider) {
            $provider = self::DEFAULT_PROVIDER;
        }

        return strtolower($provider);
    }

    public function handleUserMessageAndGetAssistantText(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider,
        string $message
    ): string {
        $provider = $this->normalizeProvider($provider);
        $message = trim($message);

        if ('' === $message) {
            throw new InvalidArgumentException('Empty message.');
        }

        $conversation = $this->getOrCreateConversation($userId, $course, $session, $provider);

        // Store user message
        $userMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('user')
            ->setContent($message)
        ;

        $this->em->persist($userMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        // Generate assistant reply (and optionally store provider conversation id)
        $result = $this->generateAssistantReplyWithMeta($provider, $course, $conversation);

        $assistantText = trim((string) $result->text);
        if ('' === $assistantText) {
            $assistantText = 'I could not generate a response right now. Please try again.';
        }

        if (null !== $result->conversationId && '' !== trim($result->conversationId)) {
            // Store provider conversation id if the provider supports it
            if ($conversation->getProviderConversationId() !== $result->conversationId) {
                $conversation->setProviderConversationId($result->conversationId);
            }
        }

        // Store assistant message
        $assistantMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('assistant')
            ->setContent($assistantText)
        ;

        $this->em->persist($assistantMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        return $assistantText;
    }

    private function generateAssistantReplyWithMeta(
        string $provider,
        Course $course,
        AiTutorConversation $conversation
    ): AiChatCompletionResult {
        $system = $this->buildSystemPrompt($course);

        $history = $this->conversationRepo->findMessages($conversation, 20);

        $providerMessages = [];
        $providerMessages[] = ['role' => 'system', 'content' => $system];

        foreach ($history as $m) {
            $providerMessages[] = [
                'role' => (string) $m->getRole(),
                'content' => (string) $m->getContent(),
            ];
        }

        $options = [
            'temperature' => 0.4,
        ];

        // Optional: if provider supports conversation continuity via an id
        if (null !== $conversation->getProviderConversationId() && '' !== trim($conversation->getProviderConversationId() ?? '')) {
            $options['conversation_id'] = $conversation->getProviderConversationId();
        }

        return $this->client->chatWithMeta($provider, $providerMessages, $options);
    }

    public function sendTutorMessageForDockedChat(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider,
        string $message,
        string $uiLang
    ): array {
        $provider = $this->normalizeProvider($provider);
        $message = trim($message);

        if ('' === $message) {
            return ['id' => 0];
        }

        $conversation = $this->getOrCreateConversation($userId, $course, $session, $provider);

        // Persist user message
        $userMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('user')
            ->setContent($message)
        ;

        $this->em->persist($userMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        // Generate assistant reply with meta (may update provider conversation id)
        $result = $this->generateAssistantReplyWithMeta($provider, $course, $conversation);

        $assistantText = trim((string) $result->text);
        if ('' === $assistantText) {
            $assistantText = 'I could not generate a response right now. Please try again.';
        }

        if (null !== $result->conversationId && '' !== trim($result->conversationId)) {
            if ($conversation->getProviderConversationId() !== $result->conversationId) {
                $conversation->setProviderConversationId($result->conversationId);
            }
        }

        // Persist assistant message
        $assistantMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('assistant')
            ->setContent($assistantText)
        ;

        $this->em->persist($assistantMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        $assistantItem = $this->toDockedChatItem($assistantMsg, $userId, $provider);

        return [
            'id' => (int) $userMsg->getId(),
            'assistant' => $assistantItem,
            'mode' => 'course',
        ];
    }

    /**
     * Returns a page of messages for the docked UI (older messages loading).
     * $visibleMessages is how many messages are already shown in the UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistoryPageAsDockedItems(
        int $userId,
        Course $course,
        string $provider,
        int $visibleMessages,
        int $pageSize = 30
    ): array {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            return [];
        }

        $total = $this->conversationRepo->countMessages($conversation);
        if ($total <= 0) {
            return [];
        }

        $visible = max(0, $visibleMessages);
        $remaining = max(0, $total - $visible);

        if ($remaining <= 0) {
            return [];
        }

        $limit = min($pageSize, $remaining);
        $offset = max(0, $total - $visible - $limit);

        $messages = $this->conversationRepo->findMessagesPage($conversation, $offset, $limit);

        $items = [];
        foreach ($messages as $m) {
            $items[] = $this->toDockedChatItem($m, $userId, $provider);
        }

        return $items;
    }

    /**
     * Returns incoming messages since a given ID for the docked UI.
     * For the AI tutor, "incoming" means assistant messages only.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIncomingSinceAsDockedItems(
        int $userId,
        Course $course,
        string $provider,
        int $sinceId
    ): array {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            return [];
        }

        $sinceId = max(0, $sinceId);
        $messages = $this->conversationRepo->findAssistantMessagesSince($conversation, $sinceId);

        $items = [];
        foreach ($messages as $m) {
            $items[] = $this->toDockedChatItem($m, $userId, $provider);
        }

        return $items;
    }

    /**
     * Stores last-seen id in session (course + provider scoped).
     */
    public function ackTutorReadUpTo(
        int $userId,
        Course $course,
        string $provider,
        int $lastSeenId
    ): int {
        $provider = $this->normalizeProvider($provider);
        $lastSeenId = max(0, $lastSeenId);

        try {
            $req = $this->requestStack->getCurrentRequest();
            if (null === $req || !$req->hasSession()) {
                return 0;
            }

            $key = $this->buildLastSeenSessionKey((int) $course->getId(), $provider);

            $current = (int) $req->getSession()->get($key, 0);
            if ($lastSeenId > $current) {
                $req->getSession()->set($key, $lastSeenId);

                return 1;
            }

            return 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function buildLastSeenSessionKey(int $courseId, string $provider): string
    {
        return 'ai_tutor_last_seen_'.$courseId.'_'.$provider;
    }

    private function getLastSeenFromSession(int $courseId, string $provider): int
    {
        try {
            $req = $this->requestStack->getCurrentRequest();
            if (null === $req || !$req->hasSession()) {
                return 0;
            }

            $key = $this->buildLastSeenSessionKey($courseId, $provider);

            return (int) $req->getSession()->get($key, 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Converts an AiTutorMessage into the same shape used by classic chat JSON items.
     *
     * @return array<string, mixed>
     */
    private function toDockedChatItem(AiTutorMessage $m, int $meUserId, string $provider): array
    {
        $id = (int) $m->getId();
        $role = (string) $m->getRole();
        $createdAt = $m->getCreatedAt();
        $ts = $createdAt ? (int) $createdAt->getTimestamp() : time();

        $isAssistant = ('assistant' === $role);

        $fromId = $isAssistant ? self::FRIEND_AI : $meUserId;
        $toId = $isAssistant ? $meUserId : self::FRIEND_AI;

        $courseId = (int) $m->getConversation()->getCourse()->getId();
        $lastSeen = $this->getLastSeenFromSession($courseId, $provider);

        // recd=1 means "read"; for assistant messages we mark as unread if above lastSeen.
        $recd = 1;
        if ($isAssistant) {
            $recd = ($id <= $lastSeen) ? 1 : 0;
        }

        $content = (string) $m->getContent();
        $safeHtml = $this->toSafeHtml($content);

        return [
            'id' => $id,
            'message' => $safeHtml,
            'date' => $ts,
            'recd' => $recd,
            'from_user_info' => $isAssistant ? $this->getAiTutorUserInfo() : api_get_user_info($meUserId, true),
            'to_user_info' => $isAssistant ? api_get_user_info($meUserId, true) : $this->getAiTutorUserInfo(),
            'from' => $fromId,
            'to' => $toId,
        ];
    }

    private function getAiTutorUserInfo(): array
    {
        return [
            'id' => self::FRIEND_AI,
            'user_id' => self::FRIEND_AI,
            'complete_name' => 'AI Tutor',
            'user_is_online_in_chat' => 1,
            'user_is_online' => 1,
            'online' => 1,
            'avatar_small' => '',
        ];
    }

    private function toSafeHtml(string $text): string
    {
        $text = trim($text);
        if ('' === $text) {
            return '';
        }

        // Keep line breaks readable in the UI, avoid raw HTML injection.
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = nl2br($escaped);

        return Security::remove_XSS($html);
    }

    public function handleUserMessageForDock(
        int $userId,
        Course $course,
        ?Session $session,
        string $provider,
        string $message
    ): array {
        $provider = $this->normalizeProvider($provider);
        $message = trim($message);

        if ('' === $message) {
            throw new InvalidArgumentException('Empty message.');
        }

        $conversation = $this->getOrCreateConversation($userId, $course, $session, $provider);

        // Persist user message
        $userMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('user')
            ->setContent($message)
        ;

        $this->em->persist($userMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        // Generate assistant reply (with meta to keep provider conversation id)
        $result = $this->generateAssistantReplyWithMeta($provider, $course, $conversation);

        $assistantText = trim((string) $result->text);
        if ('' === $assistantText) {
            $assistantText = 'I could not generate a response right now. Please try again.';
        }

        if (null !== $result->conversationId && '' !== trim($result->conversationId)) {
            if ($conversation->getProviderConversationId() !== $result->conversationId) {
                $conversation->setProviderConversationId($result->conversationId);
            }
        }

        // Persist assistant message
        $assistantMsg = (new AiTutorMessage())
            ->setConversation($conversation)
            ->setRole('assistant')
            ->setContent($assistantText)
        ;

        $this->em->persist($assistantMsg);
        $conversation->touchLastMessageAt();
        $this->em->flush();

        return [
            'conversation' => $conversation,
            'user' => $userMsg,
            'assistant' => $assistantMsg,
            'assistant_text' => $assistantText,
        ];
    }

    /**
     * Returns one "page" of messages for the dock, based on how many are already visible.
     *
     * @return AiTutorMessage[]
     */
    public function getDockMessagesPage(
        int $userId,
        Course $course,
        string $provider,
        int $visible,
        int $pageSize = 20
    ): array {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            return [];
        }

        $total = $this->conversationRepo->countMessages($conversation);
        if ($total <= 0) {
            return [];
        }

        $visible = max(0, $visible);
        $pageSize = max(1, $pageSize);

        // We want the slice just before the currently visible tail.
        // Example: total=100, visible=20, pageSize=20 => return messages[60..79]
        $end = max(0, $total - $visible);
        $start = max(0, $end - $pageSize);
        $len = max(0, $end - $start);

        if ($len <= 0) {
            return [];
        }

        return $this->conversationRepo->findMessagesSlice($conversation, $start, $len);
    }

    /**
     * @return AiTutorMessage[]
     */
    public function getDockMessagesSince(
        int $userId,
        Course $course,
        string $provider,
        int $sinceId,
        int $limit = 80
    ): array {
        $provider = $this->normalizeProvider($provider);

        $conversation = $this->conversationRepo->findOneByUserCourseProvider(
            $userId,
            (int) $course->getId(),
            $provider
        );

        if (null === $conversation) {
            return [];
        }

        return $this->conversationRepo->findMessagesSinceId($conversation, max(0, $sinceId), $limit);
    }
}
