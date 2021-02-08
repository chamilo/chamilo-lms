<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Extra fields.
 */
class Version20191206150000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('extra_field');
        if (false === $table->hasColumn('helper_text')) {
            $this->addSql('ALTER TABLE extra_field ADD helper_text text DEFAULT NULL AFTER display_text');
        }
        $this->addSql('ALTER TABLE extra_field_values CHANGE value value LONGTEXT DEFAULT NULL;');
        if (false === $table->hasColumn('description')) {
            $this->addSql('ALTER TABLE extra_field ADD description LONGTEXT DEFAULT NULL');
        }

        $table = $schema->getTable('extra_field_values');
        if (!$table->hasIndex('idx_efv_item')) {
            $this->addSql('CREATE INDEX idx_efv_item ON extra_field_values (item_id)');
        }

        // Migrate extra field fields
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        $q = $em->createQuery('SELECT v FROM Chamilo\CoreBundle\Entity\ExtraFieldValues v');

        $fieldWithFiles = \ExtraField::getExtraFieldTypesWithFiles();

        /** @var ExtraFieldValues $item */
        foreach ($q->toIterable() as $item) {
            if (in_array($item->getField()->getFieldType(), $fieldWithFiles)) {
                $path = $item->getValue();
                if (empty($path)) {
                    continue;
                }
                $filePath = $rootPath.'/app/upload/'.$path;
                if (file_exists($filePath) && !is_dir($filePath)) {
                    $fileName = basename($path);
                    $mimeType = mime_content_type($filePath);
                    $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                    $asset = new Asset();
                    $asset
                        ->setCategory(Asset::EXTRA_FIELD)
                        ->setTitle($fileName)
                        ->setFile($file)
                    ;
                    $em->persist($asset);
                    $em->flush();
                    $item->setValue($asset->getId());
                    $em->persist($item);
                }
            }

            if (0 === $counter % $batchSize) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }
    }
}
