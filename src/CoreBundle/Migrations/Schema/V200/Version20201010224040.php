<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201010224040 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'sys_announcement DB';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $sql = 'SELECT * FROM sys_announcement';
        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        $repo = $container->get(SysAnnouncementRepository::class);

        foreach ($items as $itemData) {
            $id = $itemData['id'];
            /** @var SysAnnouncement $announcement */
            $announcement = $repo->find($id);

            $legacyRoles = [
                'visible_teacher' => 'ROLE_TEACHER',
                'visible_student' => 'ROLE_STUDENT',
                'visible_guest' => 'ROLE_ANONYMOUS',
                'visible_drh' => 'ROLE_RRHH',
                'visible_session_admin' => 'ROLE_SESSION_MANAGER',
                'visible_boss' => 'ROLE_STUDENT_BOSS',
            ];

            foreach ($legacyRoles as $old => $new) {
                $visible = $itemData[$old] ?? '';
                if (1 === (int) $visible) {
                    $announcement->addRole($new);
                }
            }
            $repo->update($announcement);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
