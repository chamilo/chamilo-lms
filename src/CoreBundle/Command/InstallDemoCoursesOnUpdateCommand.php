<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\DataFixtures\DemoCoursesFixtures;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:install-demo-courses-on-update',
    description: 'Install missing bundled demo courses as private courses after a Chamilo update.',
)]
final class InstallDemoCoursesOnUpdateCommand extends Command
{
    public function __construct(
        private readonly DemoCoursesFixtures $demoCoursesFixtures,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $admin = $this->findAdministrator();

        if (!$admin instanceof User) {
            $io->error('No active platform administrator was found to own the bundled demo courses.');

            return Command::FAILURE;
        }

        try {
            $this->demoCoursesFixtures->installDemoCourses($admin, Course::REGISTERED);
        } catch (Throwable $e) {
            $io->error('Could not install bundled demo courses: '.$e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Bundled demo courses are available. Missing courses were installed as private courses.');

        return Command::SUCCESS;
    }

    private function findAdministrator(): ?User
    {
        $globalAdmins = $this->userRepository->findByRole('ROLE_GLOBAL_ADMIN', '');
        if ([] !== $globalAdmins) {
            return $globalAdmins[0];
        }

        $admins = $this->userRepository->findByRole('ROLE_ADMIN', '');

        return $admins[0] ?? null;
    }
}
