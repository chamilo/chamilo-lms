<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Schema\Schema;

final class Version20230215072918 extends AbstractMigrationChamilo
{
    private const ORM_FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate learnpath subscription';
    }

    /**
     * Learnpath subscriptions are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $lpRepo = $this->container->get(CLpRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $sessionRepo = $this->container->get(SessionRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $rows = $this->connection->fetchAllAssociative(
            "SELECT ip.c_id, ip.ref AS lp_id, ip.session_id, ip.to_user_id
             FROM c_item_property ip
             WHERE ip.tool = 'learnpath'
               AND ip.lastedit_type = 'LearnpathSubscription'
               AND ip.to_user_id IS NOT NULL
               AND ip.to_user_id <> 0
             ORDER BY ip.c_id, ip.ref"
        );

        $rowsByCourse = [];
        foreach ($rows as $row) {
            $rowsByCourse[(int) $row['c_id']][] = $row;
        }

        $processed = 0;

        foreach ($rowsByCourse as $courseId => $courseRows) {
            $course = $courseRepo->find($courseId);
            if (!$course instanceof Course) {
                continue;
            }

            $lpCache = [];
            $userCache = [];
            $sessionCache = [];

            foreach ($courseRows as $row) {
                $lpId = (int) $row['lp_id'];
                $userId = (int) $row['to_user_id'];
                $sessionId = (int) ($row['session_id'] ?? 0);

                if (!\array_key_exists($lpId, $lpCache)) {
                    $lpCache[$lpId] = $lpRepo->find($lpId);
                }
                if (null === $lpCache[$lpId]) {
                    continue;
                }

                if (!\array_key_exists($userId, $userCache)) {
                    $userCache[$userId] = $userRepo->find($userId);
                }
                if (null === $userCache[$userId]) {
                    continue;
                }

                $item = new CLpRelUser();
                $item
                    ->setUser($userCache[$userId])
                    ->setCourse($course)
                    ->setLp($lpCache[$lpId])
                ;

                if ($sessionId > 0) {
                    if (!\array_key_exists($sessionId, $sessionCache)) {
                        $sessionCache[$sessionId] = $sessionRepo->find($sessionId);
                    }
                    if (null !== $sessionCache[$sessionId]) {
                        $item->setSession($sessionCache[$sessionId]);
                    }
                }

                $this->entityManager->persist($item);
                ++$processed;

                if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();

                    $course = $courseRepo->find($courseId);
                    $lpCache = [];
                    $userCache = [];
                    $sessionCache = [];
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void {}
}
