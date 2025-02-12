<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20240602231700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Delete documents that do not have parents based on the path.';
    }

    public function up(Schema $schema): void
    {
        $documentRepo = $this->container->get(CDocumentRepository::class);

        // Query to get the documentIids
        $sql = "SELECT cd1.iid, cd1.path, cd1.c_id
                FROM c_document cd1
                LEFT JOIN c_document cd2 ON cd2.c_id = cd1.c_id AND cd2.path = SUBSTRING_INDEX(cd1.path, '/', 2)
                WHERE cd1.path LIKE '/shared_folder_session_%'
                AND cd2.iid IS NULL";
        $result = $this->connection->executeQuery($sql);
        $orphans = $result->fetchAllAssociative();

        if (empty($orphans)) {
            echo 'No orphan documents found.' . PHP_EOL;
            return;
        }

        foreach ($orphans as $itemData) {
            echo 'Deleting document with iid: ' . $itemData['iid'] . PHP_EOL;
            $document = $documentRepo->find($itemData['iid']);
            if ($document) {
                if ($document->getResourceNode()) {
                    $this->entityManager->remove($document->getResourceNode());
                }
                $this->entityManager->remove($document);
                echo 'Deleted document with iid: ' . $itemData['iid'] . PHP_EOL;
            } else {
                echo 'Document with iid ' . $itemData['iid'] . ' not found.' . PHP_EOL;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
