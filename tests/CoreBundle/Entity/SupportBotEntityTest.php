<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotNode;
use Chamilo\CoreBundle\Entity\SupportBotOption;
use Chamilo\CoreBundle\Entity\SupportBotStep;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SupportBotEntityTest extends TestCase
{
    public function testStepKeepsTextSnapshotsWhenAnOptionIsSelected(): void
    {
        $tree = (new SupportBotTree())->setTitle('Support');
        $node = (new SupportBotNode())
            ->setContent('How can I help you?')
        ;
        $tree->addNode($node);

        $option = (new SupportBotOption())
            ->setLabel('I cannot log in')
            ->setActionType(SupportBotOption::ACTION_END_CONVERSATION)
        ;
        $node->addOption($option);

        $step = (new SupportBotStep())
            ->setNode($node)
            ->setNodeContent($node->getContent())
            ->setSelectedOption($option)
        ;

        self::assertSame('How can I help you?', $step->getNodeContent());
        self::assertSame('I cannot log in', $step->getSelectedOptionLabel());
        self::assertSame($node, $option->getNode());
    }

    public function testOptionRejectsUnknownActionType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SupportBotOption())->setActionType('unknown');
    }

    public function testConversationRejectsUnknownOutcome(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SupportBotConversation())->setOutcome('unknown');
    }
}
