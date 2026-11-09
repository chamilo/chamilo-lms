<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\AiProvider;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiChatCompletionResult;
use Chamilo\CoreBundle\AiProvider\AiTutorChatService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AiTutorChatServiceTest extends TestCase
{
    public function testSelectedTextIsBoundedBeforeReachingTheProvider(): void
    {
        $service = $this->unconstructedService();
        $method = new ReflectionMethod(AiTutorChatService::class, 'buildUserMessageWithSelectedTextContext');

        $message = $method->invoke($service, 'What does it mean?', str_repeat('a', 15000));

        self::assertStringContainsString('Student question:', $message);
        self::assertStringContainsString('What does it mean?', $message);
        self::assertMatchesRegularExpression('/Selected text:\s*"""\s*a{1800}\s*"""/', $message);
    }

    public function testGlobalTutorNeverUsesCoursePromptPersistedInSession(): void
    {
        $service = $this->unconstructedService();
        $requestStack = new RequestStack();
        $request = Request::create('/account/chat/api/send', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set('ai_tutor_system_prompt', '<course_description>PRIVATE_COURSE_SECRET</course_description>');
        $requestStack->push($request);
        (new ReflectionClass(AiTutorChatService::class))->getProperty('requestStack')->setValue($service, $requestStack);

        $client = $this->createMock(AiChatCompletionClientInterface::class);
        $client->expects(self::once())
            ->method('chatWithMeta')
            ->with(
                'openai',
                self::callback(
                    static fn (array $messages): bool => 'system' === ($messages[0]['role'] ?? '')
                    && str_contains((string) ($messages[0]['content'] ?? ''), 'Chamilo')
                    && !str_contains((string) ($messages[0]['content'] ?? ''), 'PRIVATE_COURSE_SECRET')
                    && 'user' === ($messages[1]['role'] ?? '')
                    && 'Hello' === ($messages[1]['content'] ?? '')
                ),
                self::callback(static fn (array $options): bool => true === ($options['throw_on_error'] ?? false))
            )
            ->willReturn(new AiChatCompletionResult('Tutor reply'))
        ;

        (new ReflectionClass(AiTutorChatService::class))->getProperty('client')->setValue($service, $client);

        self::assertSame('Tutor reply', $service->generateGlobalAssistantReply(1, 'openai', 'Hello', 'en'));
    }

    public function testCourseTextPromptHasNoGlobalConversationHistory(): void
    {
        $service = $this->unconstructedService();
        $prompt = $service->buildContextSystemPrompt(null, 'en');

        self::assertStringContainsString('Chamilo', $prompt);
        self::assertStringNotContainsString('<course_description>', $prompt);
    }

    private function unconstructedService(): AiTutorChatService
    {
        return (new ReflectionClass(AiTutorChatService::class))->newInstanceWithoutConstructor();
    }
}
