<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Version20230204150030 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Move extrafield session image to asset';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        $dql = 'SELECT v FROM Chamilo\CoreBundle\Entity\ExtraFieldValues v';
        $dql .= ' JOIN v.field f';
        $dql .= ' WHERE f.variable = :variable AND f.itemType = :itemType';
        $q = $this->entityManager->createQuery($dql);
        $q->setParameters([
            'variable' => 'image',
            'itemType' => ExtraField::SESSION_FIELD_TYPE,
        ]);

        $sessionRepo = $this->container->get(SessionRepository::class);

        /** @var ExtraFieldValues $item */
        foreach ($q->toIterable() as $item) {
            $path = $item->getFieldValue();
            if (empty($path)) {
                continue;
            }
            $filePath = $rootPath.'/app/upload/'.$path;
            error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
            if ($this->fileExists($filePath)) {
                $fileName = basename($path);
                $mimeType = mime_content_type($filePath);
                $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                $asset = (new Asset())
                    ->setCategory(Asset::SESSION)
                    ->setTitle($fileName)
                    ->setFile($file)
                ;
                $this->entityManager->persist($asset);
                $this->entityManager->flush();
                $item->setAsset($asset);
                $this->entityManager->persist($item);

                $sessionId = $item->getItemId();

                /** @var Session $session */
                $session = $sessionRepo->find($sessionId);
                $session->setImage($asset);
                $sessionRepo->update($session);
            }

            if (($counter % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void {}
}
