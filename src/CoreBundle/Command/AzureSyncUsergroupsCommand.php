<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Usergroup;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:azure-sync-usergroups',
    description: 'Synchronize groups registered in Azure with Chamilo user groups',
)]
class AzureSyncUsergroupsCommand extends AzureSyncAbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Synchronizing groups from Azure.');

        $accessUrl = $this->accessUrlHelper->getCurrent();

        /** @var array<string, Usergroup> $groupIdByUid */
        $groupIdByUid = [];

        $admin = $this->userRepository->getRootUser();

        try {
            foreach ($this->getAzureGroups() as $azureGroupInfo) {
                $userGroup = $this->usergroupRepository->getOneByTitleInUrl($azureGroupInfo['displayName'], $accessUrl);

                if ($userGroup) {
                    $userGroup->getUsers()->clear();

                    $io->text(
                        sprintf(
                            'Class exists, all users unsubscribed: %s (ID %d)',
                            $userGroup->getTitle(),
                            $userGroup->getId()
                        )
                    );
                } else {
                    $userGroup = (new Usergroup())
                        ->setTitle($azureGroupInfo['displayName'])
                        ->setDescription($azureGroupInfo['description'])
                        ->setCreator($admin)
                    ;

                    if ('true' === $this->settingsManager->getSetting('profile.allow_teachers_to_classes')) {
                        $userGroup->setAuthorId(
                            $this->userHelper->getCurrent()->getId()
                        );
                    }

                    $userGroup->addAccessUrl($accessUrl);

                    $this->usergroupRepository->create($userGroup);

                    $io->text(sprintf('Class created: %s (ID %d)', $userGroup->getTitle(), $userGroup->getId()));
                }

                $groupIdByUid[$azureGroupInfo['id']] = $userGroup;
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $io->section('Subscribing users to groups');

        foreach ($groupIdByUid as $azureGroupUid => $group) {
            $newGroupMembers = [];

            $io->text(sprintf('Obtaining members for group (ID %d)', $group->getId()));

            try {
                foreach ($this->getAzureGroupMembers($azureGroupUid) as $azureGroupMember) {
                    if ($userId = $this->azureHelper->getUserIdByVerificationOrder($azureGroupMember)) {
                        $newGroupMembers[] = $userId;
                    }
                }
            } catch (Exception $e) {
                $io->warning($e->getMessage());

                continue;
            }

            foreach ($newGroupMembers as $newGroupMemberId) {
                $user = $this->userRepository->find($newGroupMemberId);

                $group->addUser($user);
            }

            $io->text(
                sprintf(
                    'User IDs subscribed in class (ID %d): %s',
                    $group->getId(),
                    implode(', ', $newGroupMembers)
                )
            );
        }

        $this->entityManager->flush();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}