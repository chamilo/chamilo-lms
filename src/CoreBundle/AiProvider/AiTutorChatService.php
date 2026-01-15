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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

final class AiTutorChatService
{
    /**
     * Special "friend id" used by the course chat UI to represent the AI Tutor.
     * -  0 => course public chat
     * >  0 => private chat with a user
     * -1 => AI Tutor private conversation (per user)
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
    ) {
    }

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
        } catch (\Throwable) {
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
            .($lang ? "The course is in '{$lang}' but just answer me in whatever language I talk to you. " : "Just answer me in whatever language I talk to you. ")
            ."This is an educational use, so content that would not be appropriate for children (or minors under any law) is not acceptable. "
            ."You are not available to me when I'm taking an exam, just in case I forget and I ask why you weren't there. "
            ."You must mention the course title when greeting or when the user asks what you are. "
            ."If the user asks something unrelated to the course topic, politely redirect to course-related help.";
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
            throw new \InvalidArgumentException('Empty message.');
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
}
