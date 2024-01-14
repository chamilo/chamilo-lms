<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230315115019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate images files of system templates to asset';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $sql = 'SELECT * FROM system_template';
        $result = $connection->executeQuery($sql);
        $all = $result->fetchAllAssociative();

        $table = $schema->getTable('system_template');

        if ($table->hasColumn('image')) {
            foreach ($all as $systemTemplate) {
                if (!empty($systemTemplate['image'])) {
                    /** @var SystemTemplate $template */
                    $template = $em->find('ChamiloCoreBundle:SystemTemplate', $systemTemplate['id']);
                    if ($template->hasImage()) {
                        continue;
                    }

                    $filePath = $rootPath.'/app/home/default_platform_document/template_thumb/'.$systemTemplate['image'];
                    if ($this->fileExists($filePath)) {
                        $fileName = basename($filePath);
                        $mimeType = mime_content_type($filePath);
                        $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                        $asset = (new Asset())
                            ->setCategory(Asset::SYSTEM_TEMPLATE)
                            ->setTitle($fileName)
                            ->setFile($file)
                        ;
                        $em->persist($asset);
                        $em->flush();
                        $template->setImage($asset);

                        $em->persist($template);
                        $em->flush();
                    }
                }
            }
        }
    }

    public function down(Schema $schema): void {}
}
