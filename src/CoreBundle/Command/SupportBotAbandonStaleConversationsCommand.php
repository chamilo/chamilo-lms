<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\SupportBotConversation;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'chamilo:support-bot:abandon-stale',
    description: 'Mark Support Bot conversations older than 24 hours as abandoned.',
)]
final class SupportBotAbandonStaleConversationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new DateTime();
        $threshold = (clone $now)->modify('-24 hours');

        $count = $this->entityManager->createQueryBuilder()
            ->update(SupportBotConversation::class, 'conversation')
            ->set('conversation.outcome', ':abandoned')
            ->set('conversation.currentNode', ':currentNode')
            ->set('conversation.endedAt', ':endedAt')
            ->andWhere('conversation.outcome = :inProgress')
            ->andWhere('conversation.startedAt < :threshold')
            ->setParameter('abandoned', SupportBotConversation::OUTCOME_ABANDONED)
            ->setParameter('currentNode', null)
            ->setParameter('endedAt', $now, Types::DATETIME_MUTABLE)
            ->setParameter('inProgress', SupportBotConversation::OUTCOME_IN_PROGRESS)
            ->setParameter('threshold', $threshold, Types::DATETIME_MUTABLE)
            ->getQuery()
            ->execute()
        ;

        $output->writeln(\sprintf('Abandoned %d stale Support Bot conversation(s).', (int) $count));

        return Command::SUCCESS;
    }
}
