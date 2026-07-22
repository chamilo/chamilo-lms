<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Helpers\FileIntegrityChecker;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:file-integrity:snooze',
    description: 'Temporarily pause file-integrity alerting (e.g. while deploying an update); it resumes automatically.',
)]
class FileIntegritySnoozeCommand extends Command
{
    private const DEFAULT_DURATION_SECONDS = 3600;

    public function __construct(
        private readonly FileIntegrityChecker $checker,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'duration',
                null,
                InputOption::VALUE_REQUIRED,
                'Pause duration in seconds (capped at 86400)',
                (string) self::DEFAULT_DURATION_SECONDS
            )
            ->setHelp(
                'Requires the username and password of a global administrator, so a hijacked '
                .'session alone cannot silence detection while files are tampered with. While '
                .'the pause is active, app:file-integrity:scan silently adopts the current tree '
                .'as the new baseline instead of alerting, so the window closes without leftover alerts.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $duration = max(1, (int) $input->getOption('duration'));

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $username = (string) $questionHelper->ask(
            $input,
            $output,
            new Question('Global administrator username: ')
        );

        $passwordQuestion = new Question('Password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $password = (string) $questionHelper->ask($input, $output, $passwordQuestion);

        $user = '' !== $username ? $this->userRepository->loadUserByIdentifier($username) : null;

        if (
            null === $user
            || !$user->isSuperAdmin()
            || !$this->userRepository->isPasswordValid($user, $password)
        ) {
            $io->error('Invalid credentials, or the user is not a global administrator.');

            return Command::FAILURE;
        }

        $this->checker->snooze($duration);
        $reactivatesAt = (new DateTimeImmutable())->setTimestamp($this->checker->getSnoozeUntil());

        $io->success(\sprintf(
            'File integrity alerting paused. It will automatically resume at %s.',
            $reactivatesAt->format('Y-m-d H:i:s')
        ));

        return Command::SUCCESS;
    }
}
