<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\State\Wiki\WikiAssignmentFeedbackResolver;
use Chamilo\CourseBundle\Entity\CWikiConf;
use PHPUnit\Framework\TestCase;

final class WikiAssignmentFeedbackResolverTest extends TestCase
{
    private WikiAssignmentFeedbackResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new WikiAssignmentFeedbackResolver();
    }

    public function testResolvesFeedbackOnlyForExactProgress(): void
    {
        $configuration = $this->createConfiguration();

        self::assertSame('Keep going', $this->resolver->resolve($configuration, 30));
        self::assertSame('', $this->resolver->resolve($configuration, 40));
    }

    public function testIgnoresFeedbackWithoutConfiguredProgress(): void
    {
        $configuration = $this->createConfiguration();
        $configuration
            ->setFprogress1('')
            ->setFeedback1('Do not show at zero progress')
        ;

        self::assertSame('', $this->resolver->resolve($configuration, 0));
    }

    public function testNormalizesLegacyAndModernStoredProgress(): void
    {
        self::assertSame(30, $this->resolver->normalizeStoredProgress('3'));
        self::assertSame(30, $this->resolver->normalizeStoredProgress('30'));
        self::assertSame(100, $this->resolver->normalizeStoredProgress('150'));
        self::assertSame(0, $this->resolver->normalizeStoredProgress(''));
    }

    public function testSerializesProgressForWikiStorage(): void
    {
        self::assertSame('3', $this->resolver->serializeProgress(30));
        self::assertSame('10', $this->resolver->serializeProgress(100));
        self::assertSame('', $this->resolver->serializeProgress(0));
    }

    private function createConfiguration(): CWikiConf
    {
        return (new CWikiConf())
            ->setTask('')
            ->setFeedback1('Keep going')
            ->setFeedback2('Almost there')
            ->setFeedback3('Completed')
            ->setFprogress1('3')
            ->setFprogress2('7')
            ->setFprogress3('10')
            ->setMaxText(0)
            ->setMaxVersion(0)
            ->setDelayedsubmit(0)
        ;
    }
}
