<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20230315115019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate images files of system templates to asset';
    }

    public function up(Schema $schema): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $sql = 'SELECT * FROM system_template';
        $result = $this->connection->executeQuery($sql);
        $all = $result->fetchAllAssociative();

        $table = $schema->getTable('system_template');

        if ($table->hasColumn('image')) {
            foreach ($all as $systemTemplate) {
                if (!empty($systemTemplate['image'])) {
                    /** @var SystemTemplate|null $template */
                    $template = $this->entityManager->find(SystemTemplate::class, $systemTemplate['id']);
                    if ($template->hasImage()) {
                        continue;
                    }

                    $filePath = $this->getUpdateRootPath().'/app/home/default_platform_document/template_thumb/'.$systemTemplate['image'];
                    if ($this->fileExists($filePath)) {
                        $fileName = basename($filePath);
                        $mimeType = mime_content_type($filePath);
                        $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                        $asset = (new Asset())
                            ->setCategory(Asset::SYSTEM_TEMPLATE)
                            ->setTitle($fileName)
                            ->setFile($file)
                        ;
                        $this->entityManager->persist($asset);
                        $this->entityManager->flush();
                        $template->setImage($asset);

                        $this->entityManager->persist($template);
                        $this->entityManager->flush();
                    }
                }
            }
        }
    }

    public function down(Schema $schema): void {}
}
