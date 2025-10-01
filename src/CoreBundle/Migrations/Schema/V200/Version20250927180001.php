<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use DateTime;
use DateTimeZone;
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
            ->executeQuery('SELECT * FROM portfolio ORDER BY id ASC')
            ->fetchAllAssociative()
        ;

        foreach ($portfolioRows as $portfolioRow) {
            $portfolioId = $portfolioRow['id'];
            $creationDate = new DateTime($portfolioRow['creation_date']);
            $updateDate = new DateTime($portfolioRow['update_date']);

            /** @var Portfolio $portfolio */
            $portfolio = $portfolioRepo->find($portfolioId);
            $creator = $userRepo->find($portfolioRow['user_id']);
            $course = $portfolioRow['c_id'] ? $this->findCourse($portfolioRow['c_id']) : null;
            $session = $portfolioRow['session_id'] ? $this->findSession($portfolioRow['session_id']) : null;

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
                ->setUpdatedAt($updateDate)
            ;

            $this->entityManager->flush();

            $resourceVisibility = match ($portfolio->getVisibility()) {
                Portfolio::VISIBILITY_HIDDEN => ResourceLink::VISIBILITY_DRAFT,
                Portfolio::VISIBILITY_VISIBLE => ResourceLink::VISIBILITY_PUBLISHED,
                default => ResourceLink::VISIBILITY_PENDING,
            };

            $itemsProperty = $this->connection
                ->executeQuery(
                    "SELECT * FROM c_item_property
                        WHERE tool = 'portfolio' AND ref = $portfolioId"
                )
                ->fetchAllAssociative()
            ;

            if (empty($itemsProperty) && $course) {
                $courseLink = $portfolio->addCourseLink(
                    $course,
                    $session,
                    null,
                    $resourceVisibility,
                    $creationDate,
                    $updateDate
                );

                $this->entityManager->persist($courseLink);
            } else {
                foreach ($itemsProperty as $itemProperty) {
                    $course = $itemProperty['c_id'] ? $this->findCourse($itemProperty['c_id']) : null;
                    $session = $itemProperty['session_id'] ? $this->findSession($itemProperty['session_id']) : null;
                    $toUser = $itemProperty['to_user_id'] ? $userRepo->find($itemProperty['to_user_id']) : null;
                    $insertDate = new DateTime($itemProperty['insert_date'], new DateTimeZone('UTC'));
                    $editDate = new DateTime($itemProperty['lastedit_date'], new DateTimeZone('UTC'));

                    $resourceNode->setUpdatedAt($editDate);

                    if ($toUser) {
                        $userLink = $portfolio->addUserLink(
                            $toUser,
                            $course,
                            $session,
                            null,
                            $insertDate,
                            $editDate
                        );

                        $this->entityManager->persist($userLink);
                    } else {
                        $courseLink = $portfolio->addCourseLink(
                            $course,
                            $session,
                            null,
                            $resourceVisibility,
                            $creationDate,
                            $updateDate
                        );

                        $this->entityManager->persist($courseLink);
                    }
                }
            }

            $this->entityManager->flush();
        }
    }
}
