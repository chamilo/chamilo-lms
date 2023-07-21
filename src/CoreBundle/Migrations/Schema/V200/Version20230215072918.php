<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Chamilo\CourseBundle\Repository\CLpRelUserRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20230215072918 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate learnpath subscription';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $lpRepo = $container->get(CLpRepository::class);

        /** @var CLpRelUserRepository $cLpRelUserRepo */
        $cLpRelUserRepo = $container->get(CLpRelUserRepository::class);

        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $userRepo = $container->get(UserRepository::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $admin = $this->getAdmin();


        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $sql = "SELECT * FROM c_lp WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $lps = $result->fetchAllAssociative();
            foreach ($lps as $lpData) {
                $id = $lpData['iid'];
                $lp = $lpRepo->find($id);
                $sql = "SELECT * FROM c_item_property
                        WHERE tool = 'learnpath' AND c_id = {$courseId} AND ref = {$id} AND lastedit_type = 'LearnpathSubscription'";
                $result = $connection->executeQuery($sql);
                $items = $result->fetchAllAssociative();

                if (!empty($items)) {
                    foreach ($items as $item) {
                        $sessionId = $item['session_id'] ?? 0;
                        $userId = $item['to_user_id'] ?? 0;
                        $session = $sessionRepo->find($sessionId);
                        $user = $userRepo->find($userId);
                        $item = new CLpRelUser();
                        $item
                            ->setUser($user)
                            ->setCourse($course)
                            ->setLp($lp);
                        if (!empty($session)) {
                            $item->setSession($session);
                        }
                        $em->persist($item);
                        $em->flush();
                    }
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
