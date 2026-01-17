<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Command\DoctrineMigrationsMigrateCommandDecorator;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
use Database;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216110722 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_attendance';
    }

    public function up(Schema $schema): void
    {
        $attendanceRepo = $this->container->get(CAttendanceRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $attendanceResourceType = $attendanceRepo->getResourceType();
        $admin = $this->getAdmin();

        $skipAttendances = (bool) getenv(DoctrineMigrationsMigrateCommandDecorator::SKIP_ATTENDANCES_FLAG);

        // IDs linked to gradebook (type=7) -> normalized to attendance.iid
        $gradebookIds = [];
        if ($skipAttendances) {
            $ids = $this->connection->fetchFirstColumn(
                'SELECT DISTINCT a.iid
         FROM gradebook_link gl
         INNER JOIN c_attendance a ON (a.iid = gl.ref_id OR a.id = gl.ref_id)
         WHERE gl.type = 7'
            );

            $ids = array_map('intval', $ids);
            $gradebookIds = array_fill_keys($ids, true);
        }

        /*
         * Title/slug performance hack:
         * Backup titles, temporarily make titles unique, migrate, then restore titles.
         * We do it only for the attendances that WILL be migrated when skip is enabled.
         */
        if ($skipAttendances) {
            $attendancesBackup = $this->connection->fetchAllAssociative(
                'SELECT a.iid, a.title
                 FROM c_attendance a
                 WHERE a.iid IN (SELECT ref_id FROM gradebook_link WHERE type = 7)'
            );

            $this->connection->executeStatement(
                "UPDATE c_attendance
                 SET title = CONCAT(title, '-', iid)
                 WHERE iid IN (SELECT ref_id FROM gradebook_link WHERE type = 7)"
            );
        } else {
            $attendancesBackup = $this->connection->fetchAllAssociative('SELECT iid, title FROM c_attendance');
            $this->connection->executeStatement("UPDATE c_attendance SET title = CONCAT(title, '-', iid)");
        }

        /**
         * Instead of iterating ALL courses and relying on c_attendance.c_id,
         * we iterate only course ids that have attendances through c_item_property.
         */
        $courseIds = $this->connection->fetchFirstColumn(
            "SELECT DISTINCT c_id
             FROM c_item_property
             WHERE tool = 'attendance'
             ORDER BY c_id"
        );

        foreach ($courseIds as $courseId) {
            $courseId = (int) $courseId;
            $course = $courseRepo->find($courseId);

            if (null === $course) {
                $this->write(\sprintf('Course %s not found - skipping attendances migration', $courseId));

                continue;
            }

            // Fetch attendance IDs belonging to this course via c_item_property
            $rows = $this->connection->fetchAllAssociative(
                "SELECT a.iid
                 FROM c_attendance a
                 INNER JOIN c_item_property ip
                    ON ip.tool = 'attendance'
                   AND ip.ref = a.iid
                   AND ip.c_id = :courseId
                 ORDER BY a.iid",
                ['courseId' => $courseId]
            );

            foreach ($rows as $row) {
                $id = (int) $row['iid'];

                // If skip enabled, we only migrate gradebook-linked attendances
                if ($skipAttendances && !isset($gradebookIds[$id])) {
                    $this->write(\sprintf('Attendance %s is not linked to gradebook - skipping', $id));

                    continue;
                }

                /** @var CAttendance|null $resource */
                $resource = $attendanceRepo->find($id);
                if (null === $resource || $resource->hasResourceNode()) {
                    continue;
                }

                $ok = $this->fixItemProperty(
                    'attendance',
                    $attendanceRepo,
                    $course,
                    $admin,
                    $resource,
                    $course,
                    [],
                    $attendanceResourceType
                );

                if (false === $ok) {
                    continue;
                }

                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }
        }

        // Restore attendance title and resource_node title
        $db = new Database();
        $db->setManager($this->entityManager);

        foreach ($attendancesBackup as $attendance) {
            $iid = (int) $attendance['iid'];
            $titleForDatabase = $db->escape_string((string) $attendance['title']);

            $this->connection->executeStatement(
                "UPDATE c_attendance SET title = '{$titleForDatabase}' WHERE iid = {$iid}"
            );

            $this->connection->executeStatement(
                "UPDATE resource_node
                 SET title = '{$titleForDatabase}'
                 WHERE id IN (SELECT resource_node_id FROM c_attendance WHERE iid = {$iid})"
            );
        }
    }
}
