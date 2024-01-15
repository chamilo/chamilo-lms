<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\SettingsCurrentRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20240112103400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Change disk_quota of course to megabyte';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $settingRepo = $container->get(SettingsCurrentRepository::class);

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $diskQuotaInBytes = $course->getDiskQuota();
            if (null !== $diskQuotaInBytes) {
                $diskQuotaInMegabytes = $diskQuotaInBytes / (1024 * 1024);
                $course->setDiskQuota($diskQuotaInMegabytes);
                $em->persist($course);
            }
        }
        $em->flush();

        $setting = $settingRepo->findOneBy(['variable' => 'default_document_quotum']);
        if ($setting) {
            $selectedValueInBytes = (int) $setting->getSelectedValue() / (1024 * 1024);
            $setting->setSelectedValue((string) $selectedValueInBytes);
        }

        $setting = $settingRepo->findOneBy(['variable' => 'default_group_quotum']);
        if ($setting) {
            $selectedValueInBytes = (int) $setting->getSelectedValue() / (1024 * 1024);
            $setting->setSelectedValue((string) $selectedValueInBytes);
        }
        $em->flush();
    }
}
