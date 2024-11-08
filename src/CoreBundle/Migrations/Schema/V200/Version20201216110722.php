<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Repository\CAttendanceRepository;
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
        // $attendanceRepo = $container->get(CAttendanceCalendar::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        // The title of the attendance table is used to create the slug in the resource_node tbale.
        // Before creating a new registry in resource_node, Doctrine is looking for all the registries that start with the same name
        // It then find the last one depending on the name and create new one with the "title-number" 
        // where title is the title of the attendance and the number is the max number all ready existing + 1
        // Since in Chamilo 1.11.x the name of the first attendance is created automatically we have a lot of attendance with the same name
        // This make the migration to take a really long time if we have many attendance because this process is taking more and more time
        // when creating more registry with the same name.
        // So to avoid this process taking so much time :
        // * Save temporarly the title and the id of all the attendance in a PHP Array
        // * we modify the title of the attendance to make it unique during the migration by adding the iid at the end
        // * We then process the migration
        // * At the end we restore the title without the iid and also the resource_node.title

        $sql = "SELECT iid, title FROM c_attendance";
        $result = $this->connection->executeQuery($sql);
        $attendancesBackup = $result->fetchAllAssociative();
        $sqlUpdateTitle = "UPDATE c_attendance SET title = CONCAT(title, '-', iid)";
        $resultUpdate = $this->connection->executeQuery($sqlUpdateTitle);

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // c_thematic.
            $sql = "SELECT * FROM c_attendance WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CAttendance $resource */
                $resource = $attendanceRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'attendance',
                    $attendanceRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
                $this->entityManager->flush();
            }
        }
        // Restoring attendance title and resource_node title
        foreach ($attendancesBackup as $attendance) {
            $sqlRestoreAttendance = "UPDATE c_attendance SET title = '{$attendance['title']}' where iid = {$attendance['iid']}";
            $resultUpdate = $this->connection->executeQuery($sqlRestoreAttendance);
            $sqlUpdateResourceNode = "UPDATE resource_node SET title = '{$attendance['title']}' where id in (SELECT resource_node_id FROM c_attendance where iid = {$attendance['iid']})";
            $resultUpdate = $this->connection->executeQuery($sqlUpdateResourceNode);
        }
    }
}
