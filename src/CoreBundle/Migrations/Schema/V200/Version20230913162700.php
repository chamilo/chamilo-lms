<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20230913162700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename old document path by resource file path';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $container->get(CDocumentRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $courseDirectory = $course->getDirectory();
            $sql = "SELECT * FROM c_tool_intro WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            if (!empty($items)) {
                foreach ($items as $itemData) {
                    $introText = $itemData['intro_text'];
                    $introId = $itemData['iid'];
                    $pattern = '/(src|href)=(["\'])(\/courses\/' . preg_quote($courseDirectory, '/') . '\/[^"\']+\.\w+)\2/i';
                    preg_match_all($pattern, $introText, $matches);
                    $videosSrcPath = $matches[3];
                    if (!empty($videosSrcPath)) {
                        foreach ($videosSrcPath as $index => $videoPath) {
                            $documentPath = str_replace('/courses/' . $courseDirectory . '/document/', '/', $videoPath);
                            $sql = "SELECT iid, path, resource_node_id
                                    FROM c_document
                                    WHERE
                                          c_id = $courseId AND
                                          path LIKE '$documentPath'
                                    ";
                            $result = $connection->executeQuery($sql);
                            $documents = $result->fetchAllAssociative();
                            if (!empty($documents)) {
                                foreach ($documents as $documentData) {
                                    $resourceNodeId = (int) $documentData['resource_node_id'];
                                    $documentFile = $documentRepo->getResourceFromResourceNode($resourceNodeId);
                                    if ($documentFile) {
                                        $newUrl = $documentRepo->getResourceFileUrl($documentFile);
                                        if (!empty($newUrl)) {
                                            $patternForReplacement = '/' . $matches[1][$index] . '=(["\'])' . preg_quote($videoPath, '/') . '\1/i';
                                            $replacement = $matches[1][$index] . '=$1' . $newUrl . '$1';
                                            $introText = preg_replace($patternForReplacement, $replacement, $introText);
                                            error_log('$documentPath ->' . $documentPath);
                                            error_log('newUrl ->' . $newUrl);
                                            error_log('$introId ->' . $introId);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // Update the new intro text with resource url.
                    $sql = "UPDATE c_tool_intro SET intro_text = :newIntroText WHERE iid = :introId";
                    $params = [
                        'newIntroText' => $introText,
                        'introId' => $introId,
                    ];
                    $connection->executeQuery($sql, $params);
                }
            }
        }
    }
}
