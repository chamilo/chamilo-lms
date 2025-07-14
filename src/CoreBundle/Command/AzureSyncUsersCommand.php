<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:azure-sync-users',
    description: 'Synchronize users accounts registered in Azure with Chamilo user accounts',
)]
class AzureSyncUsersCommand extends AzureSyncAbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronizing users from Azure.');

        /** @var array<string, int> $azureCreatedUserIdList */
        $azureCreatedUserIdList = [];

        try {
            foreach ($this->getAzureUsers() as $azureUserInfo) {
                try {
                    $user = $this->azureHelper->registerUser($azureUserInfo);
                } catch (NonUniqueResultException $e) {
                    $io->warning($e->getMessage());

                    continue;
                }

                $azureCreatedUserIdList[$azureUserInfo['id']] = $user->getId();

                $io->text(
                    \sprintf('User (ID %d) with received info: %s ', $user->getId(), serialize($azureUserInfo))
                );
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $io->section('Updating users status');

        $roleActions = $this->azureHelper->getUpdateActionByRole();

        foreach ($this->providerParams['group_id'] as $userRole => $groupUid) {
            if (empty($groupUid)) {
                continue;
            }

            try {
                $azureGroupMembersInfo = iterator_to_array($this->getAzureGroupMembers($groupUid));
            } catch (Exception $e) {
                $io->warning($e->getMessage());

                continue;
            }

            $azureGroupMembersUids = array_column($azureGroupMembersInfo, 'id');

            foreach ($azureGroupMembersUids as $azureGroupMembersUid) {
                $userId = $azureCreatedUserIdList[$azureGroupMembersUid] ?? null;

                if (!$userId) {
                    continue;
                }

                if (isset($roleActions[$userRole])) {
                    $user = $this->userRepository->find($userId);

                    $roleActions[$userRole]($user);

                    $io->text(
                        \sprintf('User (ID %d) status %s', $userId, $userRole)
                    );
                }
            }

            $this->entityManager->flush();
        }

        if ($this->providerParams['deactivate_nonexisting_users']
            && !$this->providerParams['script_users_delta']
        ) {
            $io->section('Trying deactivate non-existing users in Azure');

            $users = $this->userRepository->findByAuthsource(UserAuthSource::AZURE);

            $chamiloUserIdList = array_map(
                fn (User $user) => $user->getId(),
                $users
            );

            $nonExistingUsers = array_diff($chamiloUserIdList, $azureCreatedUserIdList);

            $this->userRepository->deactivateUsers($nonExistingUsers);

            $io->text(
                \sprintf(
                    'Deactivated users IDs: %s',
                    implode(', ', $nonExistingUsers)
                )
            );
        }

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
