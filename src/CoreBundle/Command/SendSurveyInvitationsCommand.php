<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Service\Survey\SurveyInvitationDelivery;
use Chamilo\CourseBundle\Entity\CSurveyAutoInvitation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:send-survey-invitations', description: 'Send opted-in survey invitations to newly enrolled learners.')]
final class SendSurveyInvitationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SurveyInvitationDelivery $delivery
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('send', null, InputOption::VALUE_NONE, 'Send invitations. Without this flag, only report eligible recipients.')
            ->addOption('survey-id', null, InputOption::VALUE_REQUIRED, 'Limit the run to one survey identifier.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $raw = $input->getOption('survey-id');
        if (null !== $raw && (!ctype_digit((string) $raw) || (int) $raw < 1)) {
            $output->writeln('<error>Invalid survey identifier.</error>');

            return Command::INVALID;
        }
        $send = (bool) $input->getOption('send');
        $failed = false;
        $criteria = null === $raw ? [] : ['survey' => (int) $raw];
        foreach ($this->entityManager->getRepository(CSurveyAutoInvitation::class)->findBy($criteria) as $policy) {
            try {
                $result = $this->delivery->processPolicy($policy, $send);
                $output->writeln(\sprintf('Survey %d, course %d, session %d, portal %d: pending=%d sent=%d failed=%d', $policy->survey->getIid(), $policy->course->getId(), $policy->session?->getId() ?? 0, $policy->portal->getId(), $result['pending'], $result['sent'], $result['failed']));
                $failed = $failed || $result['failed'] > 0;
            } catch (Throwable) {
                $output->writeln('<error>Unable to process one survey invitation policy.</error>');
                $failed = true;
                if (!$this->entityManager->isOpen()) {
                    break;
                }
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
