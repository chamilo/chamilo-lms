<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Exception;

class Version20250927180001 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate portfolio items to resource nodes';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        /** @var ResourceRepository $portfolioRepo */
        $portfolioRepo = $this->container->get(PortfolioRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $portfolioRows = $this->connection
            ->executeQuery("SELECT * FROM portfolio ORDER BY id ASC")
            ->fetchAllAssociative();

        foreach ($portfolioRows as $portfolioRow) {
            $portfolioId = $portfolioRow['id'];
            $creatorId = $portfolioRow['user_id'];
            $courseId = $portfolioRow['c_id'];
            $sessionId = $portfolioRow['session_id'];
            $creationDate = new DateTime($portfolioRow['creation_date']);
            $updateDate = new DateTime($portfolioRow['update_date']);

            /** @var Portfolio $portfolio */
            $portfolio = $portfolioRepo->find($portfolioId);
            $creator = $userRepo->find($creatorId);
            $course = $courseId ? $this->findCourse($courseId) : null;
            $session = $sessionId ? $this->findSession($sessionId) : null;

            $portfolio->setParent($creator);

            $resourceNode = $portfolioRepo->addResourceNode(
                $portfolio,
                $creator,
                $creator
            );

            $this->entityManager->persist($resourceNode);
            $this->entityManager->flush();

            $resourceNode
                ->setCreatedAt($creationDate)
                ->setUpdatedAt($updateDate);

            $this->entityManager->flush();

            if ($course) {
                $resourceVisibility = match ($portfolio->getVisibility()) {
                    Portfolio::VISIBILITY_HIDDEN => ResourceLink::VISIBILITY_DRAFT,
                    Portfolio::VISIBILITY_VISIBLE => ResourceLink::VISIBILITY_PUBLISHED,
                    default => ResourceLink::VISIBILITY_PENDING,
                };

                $courseLink = $portfolio->addCourseLink(
                    $course,
                    $session,
                    null,
                    $resourceVisibility,
                    $creationDate,
                    $updateDate
                );

                $this->entityManager->persist($courseLink);
                $this->entityManager->flush();


                $itemsProperty = $this->connection
                    ->executeQuery(
                        "SELECT * FROM c_item_property
                        WHERE tool = 'portfolio' AND ref = $portfolioId"
                    )
                    ->fetchAllAssociative();

                foreach ($itemsProperty as $itemProperty) {
                    if (empty($itemProperty['to_user_id'])) {
                        continue;
                    }

                    /** @var User|null $toUser */
                    $toUser = $userRepo->find($itemProperty['to_user_id']);

                    if (!$toUser) {
                        continue;
                    }

                    $insertDate = new DateTime($itemProperty['insert_date']);
                    $lastEditDate = new DateTime($itemProperty['lastedit_date']);

                    $userLink = $portfolio->addUserLink(
                        $toUser,
                        $course,
                        $session,
                        null,
                        $insertDate,
                        $lastEditDate
                    );

                    $this->entityManager->persist($userLink);
                    $this->entityManager->flush();
                }
            }
        }
    }
}
