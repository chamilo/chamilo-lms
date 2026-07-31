<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Survey\TrainingSatisfactionSurveyCreator;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class TrainingSatisfactionSurveyCreatorTest extends KernelTestCase
{
    public function testFallbackQuestionsAreTranslatedThroughTheCatalogueForAnyLanguage(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $creator = $container->get(TrainingSatisfactionSurveyCreator::class);

        $reflection = new ReflectionMethod($creator, 'fallbackQuestions');
        $reflection->setAccessible(true);

        $questionSet = $reflection->invoke($creator, 'en_US');

        self::assertSame(
            'Your feedback will help us improve future training sessions.',
            $questionSet['introduction']
        );
        self::assertSame('Thank you for sharing your feedback.', $questionSet['thanks']);
        self::assertCount(7, $questionSet['questions']);

        $satisfactionQuestions = \array_slice($questionSet['questions'], 0, 5);
        foreach ($satisfactionQuestions as $question) {
            self::assertSame('multiplechoice', $question['type']);
            self::assertTrue($question['required']);
            self::assertSame(
                ['Very satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very dissatisfied'],
                $question['options']
            );
        }

        self::assertSame(
            'How satisfied are you with the overall quality of the training?',
            $satisfactionQuestions[0]['text']
        );

        $recommendationQuestion = $questionSet['questions'][5];
        self::assertSame('Would you recommend this training to other people?', $recommendationQuestion['text']);
        self::assertSame('yesno', $recommendationQuestion['type']);
        self::assertSame(['Yes', 'No'], $recommendationQuestion['options']);

        $openQuestion = $questionSet['questions'][6];
        self::assertSame('What should we improve in future editions?', $openQuestion['text']);
        self::assertSame('open', $openQuestion['type']);
        self::assertFalse($openQuestion['required']);
        self::assertSame([], $openQuestion['options']);
    }

    /**
     * The Spanish-only special case has been removed: any language now
     * resolves through the translation catalogue (falling back to English
     * when a translation is not yet available), instead of a hardcoded
     * Spanish string set.
     */
    public function testFallbackQuestionsNoLongerHardcodeSpanish(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $creator = $container->get(TrainingSatisfactionSurveyCreator::class);

        $reflection = new ReflectionMethod($creator, 'fallbackQuestions');
        $reflection->setAccessible(true);

        $spanishQuestionSet = $reflection->invoke($creator, 'es');

        self::assertCount(7, $spanishQuestionSet['questions']);
        self::assertNotSame('', trim((string) $spanishQuestionSet['introduction']));
        self::assertNotSame('', trim((string) $spanishQuestionSet['questions'][0]['text']));
    }
}
