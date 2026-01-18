<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Schema\Schema;
use Exception;

class Version20250927180002 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate portfolio comments to resource nodes';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        /** @var ResourceRepository $portfolioRepo */
        $portfolioRepo = $this->container->get(PortfolioRepository::class);

        /** @var ResourceRepository $commentRepo */
        $commentRepo = $this->container->get(PortfolioCommentRepository::class);

        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);

        $commentRows = $this->connection
            ->executeQuery('SELECT * FROM portfolio_comment ORDER BY id ASC')
            ->fetchAllAssociative()
        ;

        foreach ($commentRows as $commentRow) {
            /** @var PortfolioComment $comment */
            $comment = $commentRepo->find($commentRow['id']);

            /** @var User $author */
            $author = $userRepo->find($commentRow['author_id']);

            /** @var Portfolio $item */
            $item = $portfolioRepo->find($commentRow['item_id']);

            /** @var PortfolioComment $parent */
            $parent = $commentRow['parent_id'] ? $commentRepo->find($commentRow['parent_id']) : null;
            $visibility = (int) $commentRow['visibility'];
            $creationDate = new DateTime($commentRow['date']);
            $resourceParent = $parent ?: $item;

            $comment->setParent($resourceParent);

            $resourceNode = $commentRepo->addResourceNode(
                $comment,
                $author,
                $resourceParent,
            );

            $this->entityManager->persist($resourceNode);
            $this->entityManager->flush();

            $resourceNode
                ->setCreatedAt($creationDate)
                ->setUpdatedAt($creationDate)
            ;

            $this->entityManager->flush();

            $courseLinkVisibility = match ($visibility) {
                PortfolioComment::VISIBILITY_VISIBLE => ResourceLink::VISIBILITY_PUBLISHED,
                default => ResourceLink::VISIBILITY_PENDING,
            };

            $itemsProperty = $this->connection
                ->executeQuery(
                    "SELECT * FROM c_item_property
                        WHERE tool = 'portfolio_comment' AND ref = {$comment->getId()}"
                )
                ->fetchAllAssociative()
            ;

            if (empty($itemsProperty)) {
                $itemRow = $this->connection
                    ->executeQuery("SELECT * FROM portfolio WHERE id = {$commentRow['item_id']}")
                    ->fetchAssociative()
                ;

                if ($itemRow && !empty($itemRow['c_id'])) {
                    $course = $this->findCourse($itemRow['c_id']);
                    $session = $itemRow['session_id'] ? $this->findSession($itemRow['session_id']) : null;
                    $creationDate = new DateTime($itemRow['creation_date']);
                    $updateDate = new DateTime($itemRow['update_date']);

                    $courseLink = $comment->addCourseLink(
                        $course,
                        $session,
                        null,
                        $courseLinkVisibility,
                        $creationDate,
                        $updateDate
                    );

                    $this->entityManager->persist($courseLink);
                }
            } else {
                foreach ($itemsProperty as $itemProperty) {
                    $course = $itemProperty['c_id'] ? $this->findCourse($itemProperty['c_id']) : null;
                    $session = $itemProperty['session_id'] ? $this->findSession($itemProperty['session_id']) : null;
                    $toUser = $itemProperty['to_user_id'] ? $userRepo->find($itemProperty['to_user_id']) : null;
                    $insertDate = new DateTime($itemProperty['insert_date'], new DateTimeZone('UTC'));
                    $editDate = new DateTime($itemProperty['lastedit_date'], new DateTimeZone('UTC'));

                    $resourceNode->setUpdatedAt($editDate);

                    if ($toUser) {
                        $userLink = $comment->addUserLink(
                            $toUser,
                            $course,
                            $session,
                            null,
                            $insertDate,
                            $editDate,
                        );

                        $this->entityManager->persist($userLink);
                    } else {
                        $courseLink = $comment->addCourseLink(
                            $course,
                            $session,
                            null,
                            $courseLinkVisibility,
                            $insertDate,
                            $editDate
                        );

                        $this->entityManager->persist($courseLink);
                    }
                }
            }

            $this->entityManager->flush();
        }
    }
}
