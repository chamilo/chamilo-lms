<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20210923090920 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Course pictures';
    }

    public function up(Schema $schema): void
    {
        $em = $this->getEntityManager();
        $container = $this->getContainer();

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $illustrationRepo = $container->get(IllustrationRepository::class);
        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $directory = $course->getDirectory();
            if (empty($directory)) {
                continue;
            }
            $picturePath = $rootPath.'/app/courses/'.$directory.'/course-pic.png';
            $admin = $this->getAdmin();

            if ($illustrationRepo->hasIllustration($course)) {
                continue;
            }

            if ($this->fileExists($picturePath)) {
                $mimeType = mime_content_type($picturePath);
                $uploadFile = new UploadedFile($picturePath, 'course-pic', $mimeType, null, true);
                $illustrationRepo->addIllustration(
                    $course,
                    $admin,
                    $uploadFile
                );
                $em->persist($course);
                $em->flush();
            }
        }
    }
}
