<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224245 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->migrateTagsFromInboxMessages();
    }

    private function migrateTagsFromInboxMessages(): void
    {
        // File generated in the Version20180928172830 migration
        $messageTagsInfo = $this->readFile(Version20200821224230::INBOX_TAGS_FILE);

        if (empty($messageTagsInfo)) {
            $this->write(Version20200821224230::INBOX_TAGS_FILE.' file not found. Exiting.');

            return;
        }

        $oldMessageTagsInfo = unserialize($messageTagsInfo);

        $messageRelUserRepo = $this->entityManager->getRepository(MessageRelUser::class);
        $tagRepo = $this->entityManager->getRepository(MessageTag::class);

        foreach ($oldMessageTagsInfo as $rowMessageTag) {
            $message = $this->entityManager->find(Message::class, $rowMessageTag['m_id']);
            $receiver = $this->entityManager->find(User::class, $rowMessageTag['m_receiver_id']);

            $messageTag = $tagRepo->findOneBy(['tag' => $rowMessageTag['t_tag'], 'user' => $receiver]);

            if (!$messageTag) {
                $messageTag = (new MessageTag())
                    ->setTag($rowMessageTag['t_tag'])
                    ->setUser($receiver)
                ;
            }

            $messageRelUser = $messageRelUserRepo->findOneBy(['message' => $message, 'receiver' => $receiver]);

            if ($messageRelUser) {
                $messageRelUser->addTag($messageTag);

                $this->entityManager->persist($messageRelUser);
                $this->entityManager->flush();
            }
        }

        $this->entityManager->clear();

        $this->removeFile(Version20200821224230::INBOX_TAGS_FILE);
    }
}
