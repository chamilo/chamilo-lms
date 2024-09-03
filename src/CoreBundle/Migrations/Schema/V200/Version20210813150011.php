<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Version20210813150011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate skill badges';
    }

    public function up(Schema $schema): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        // $skillRepo = $container->get(SkillRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Skill c');

        /** @var Skill $skill */
        foreach ($q->toIterable() as $skill) {
            if ($skill->hasAsset()) {
                continue;
            }

            $icon = $skill->getIcon();

            if (empty($icon)) {
                continue;
            }

            $filePath = $this->getUpdateRootPath().'/app/upload/badges/'.$icon;
            error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
            if ($this->fileExists($filePath)) {
                $mimeType = mime_content_type($filePath);
                $fileName = basename($filePath);
                $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);

                $asset = (new Asset())
                    ->setCategory(Asset::SKILL)
                    ->setTitle($fileName)
                    ->setFile($file)
                ;

                $skill->setAsset($asset);
                $this->entityManager->persist($skill);
                $this->entityManager->flush();
            }
        }
    }
}
