<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\TrackEDownloads;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20240515124400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Find and process TrackEDownloads entries with null resourceLink, linking them to ResourceNodes and updating downloads.';
    }

    public function up(Schema $schema): void
    {
        $trackEDownloadsRepository = $this->entityManager->getRepository(TrackEDownloads::class);
        $resourceFileRepository = $this->entityManager->getRepository(ResourceFile::class);
        $resourceNodeRepository = $this->entityManager->getRepository(ResourceNode::class);
        $userRepository = $this->entityManager->getRepository(User::class);

        $batchSize = 100;
        $offset = 0;
        $success = true;

        while (true) {
            $downloads = $trackEDownloadsRepository->createQueryBuilder('t')
                ->where('t.resourceLink IS NULL')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult()
            ;

            if (empty($downloads)) {
                break;
            }

            $this->entityManager->beginTransaction();

            try {
                foreach ($downloads as $download) {
                    $downDocPath = $download->getDownDocPath();
                    $fileName = basename($downDocPath);

                    $resourceFile = $resourceFileRepository->findOneBy(['originalName' => $fileName]);

                    if ($resourceFile) {
                        $resourceNode = $resourceFile->getResourceNode();

                        if ($resourceNode) {
                            $downUserId = $download->getDownUserId();
                            $user = $userRepository->find($downUserId);
                            if (null === $user) {
                                $user = $this->getAdmin();
                            }

                            $firstResourceLink = $resourceNode->getResourceLinks()->first();
                            if ($firstResourceLink && $user) {
                                $resourceLinkId = $firstResourceLink->getId();
                                $url = $resourceNode->getResourceFiles()->first()->getOriginalName();
                                echo "Resource link $resourceLinkId Down id {$download->getDownId()} for $url: user ".$user->getFullname()."\n";

                                $this->connection->executeUpdate('UPDATE track_e_downloads SET resource_link_id = ? WHERE down_id = ?', [$resourceLinkId, $download->getDownId()]);
                            }
                        }
                    }
                }

                $this->entityManager->commit();
            } catch (Exception $e) {
                $this->entityManager->rollback();
                $success = false;
                echo "Failed for download ID {$download->getDownId()}: ".$e->getMessage()."\n";

                break;
            }

            $offset += $batchSize;
        }

        // Only delete if all updates were successful
        if ($success) {
            $this->connection->executeUpdate('DELETE FROM track_e_downloads WHERE resource_link_id IS NULL');
        } else {
            echo "Process failed. No records were deleted.\n";
        }
    }

    public function down(Schema $schema): void {}
}
