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
        $em = $this->getEntityManager();

        $connection = $em->getConnection();

        $attendanceRepo = $this->container->get(CAttendanceRepository::class);
        // $attendanceRepo = $container->get(CAttendanceCalendar::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            // c_thematic.
            $sql = "SELECT * FROM c_attendance WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
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

                $em->persist($resource);
                $em->flush();
            }
        }
    }
}
