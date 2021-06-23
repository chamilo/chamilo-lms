<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use ExtraField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Version20191206150030 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'ExtraFieldValues changes';
    }

    public function up(Schema $schema): void
    {
        // Migrate extra field fields
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        $q = $em->createQuery('SELECT v FROM Chamilo\CoreBundle\Entity\ExtraFieldValues v');

        $fieldWithFiles = ExtraField::getExtraFieldTypesWithFiles();

        /** @var ExtraFieldValues $item */
        foreach ($q->toIterable() as $item) {
            if (\in_array($item->getField()->getFieldType(), $fieldWithFiles, true)) {
                $path = $item->getValue();
                if (empty($path)) {
                    continue;
                }
                $filePath = $rootPath.'/app/upload/'.$path;
                if (file_exists($filePath) && !is_dir($filePath)) {
                    $fileName = basename($path);
                    $mimeType = mime_content_type($filePath);
                    $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                    $asset = (new Asset())
                        ->setCategory(Asset::EXTRA_FIELD)
                        ->setTitle($fileName)
                        ->setFile($file)
                    ;
                    $em->persist($asset);
                    $em->flush();
                    $item->setAsset($asset);
                    //$item->setValue((string) $asset->getId());
                    $em->persist($item);
                }
            }

            if (($counter % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }
        $em->flush();
        $em->clear();
    }
}
