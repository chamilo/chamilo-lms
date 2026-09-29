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
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;

final class Version20250927180002 extends AbstractMigrationChamilo
{
    private const int READ_BATCH_SIZE = 250;
    private const int FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate portfolio comments to resource nodes using keyset reads and bounded ORM flushes';
    }

    public function up(Schema $schema): void
    {
        /** @var PortfolioRepository $portfolioRepo */
        $portfolioRepo = $this->container->get(PortfolioRepository::class);

        /** @var PortfolioCommentRepository $commentRepo */
        $commentRepo = $this->container->get(PortfolioCommentRepository::class);

        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);

        $lastId = 0;
        $seen = 0;
        $migrated = 0;
        $skipped = 0;
        $pendingFlush = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    <<<'SQL'
SELECT
    comment.*,
    item.c_id AS item_c_id,
    item.session_id AS item_session_id,
    item.creation_date AS item_creation_date,
    item.update_date AS item_update_date
FROM portfolio_comment comment
INNER JOIN portfolio item ON item.id = comment.item_id
WHERE comment.id > :lastId
ORDER BY comment.id ASC
LIMIT %d
SQL,
                    self::READ_BATCH_SIZE
                ),
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            $lastId = (int) $rows[array_key_last($rows)]['id'];
            $properties = $this->loadItemProperties('portfolio_comment', array_column($rows, 'id'));

            foreach ($rows as $row) {
                ++$seen;
                $commentId = (int) $row['id'];
                $comment = $commentRepo->find($commentId);
                $author = $userRepo->find((int) $row['author_id']);
                $item = $portfolioRepo->find((int) $row['item_id']);
                $parent = !empty($row['parent_id'])
                    ? $commentRepo->find((int) $row['parent_id'])
                    : null;

                if (!$comment instanceof PortfolioComment
                    || !$author instanceof User
                    || !$item instanceof Portfolio
                    || $comment->hasResourceNode()
                ) {
                    ++$skipped;

                    continue;
                }

                $creationDate = new DateTime((string) $row['date']);
                $resourceParent = $parent instanceof PortfolioComment ? $parent : $item;
                $comment->setParent($resourceParent);

                $resourceNode = $commentRepo->addResourceNode($comment, $author, $resourceParent);
                $resourceNode->setCreatedAt($creationDate)->setUpdatedAt($creationDate);
                $this->entityManager->persist($resourceNode);

                $linkVisibility = PortfolioComment::VISIBILITY_VISIBLE === (int) $row['visibility']
                    ? ResourceLink::VISIBILITY_PUBLISHED
                    : ResourceLink::VISIBILITY_PENDING;

                $itemProperties = $properties[$commentId] ?? [];
                if ([] === $itemProperties && !empty($row['item_c_id'])) {
                    $course = $this->findCourse((int) $row['item_c_id']);
                    $session = !empty($row['item_session_id'])
                        ? $this->findSession((int) $row['item_session_id'])
                        : null;
                    $itemCreationDate = new DateTime((string) $row['item_creation_date']);
                    $itemUpdateDate = new DateTime((string) $row['item_update_date']);

                    $this->entityManager->persist(
                        $comment->addCourseLink(
                            $course,
                            $session,
                            null,
                            $linkVisibility,
                            $itemCreationDate,
                            $itemUpdateDate
                        )
                    );
                } else {
                    foreach ($itemProperties as $itemProperty) {
                        $course = !empty($itemProperty['c_id'])
                            ? $this->findCourse((int) $itemProperty['c_id'])
                            : null;
                        $session = !empty($itemProperty['session_id'])
                            ? $this->findSession((int) $itemProperty['session_id'])
                            : null;
                        $toUser = !empty($itemProperty['to_user_id'])
                            ? $userRepo->find((int) $itemProperty['to_user_id'])
                            : null;
                        $insertDate = new DateTime((string) $itemProperty['insert_date'], new DateTimeZone('UTC'));
                        $editDate = new DateTime((string) $itemProperty['lastedit_date'], new DateTimeZone('UTC'));

                        $resourceNode->setUpdatedAt($editDate);
                        if ($toUser instanceof User) {
                            $this->entityManager->persist(
                                $comment->addUserLink(
                                    $toUser,
                                    $course,
                                    $session,
                                    null,
                                    $insertDate,
                                    $editDate
                                )
                            );
                        } elseif (null !== $course) {
                            $this->entityManager->persist(
                                $comment->addCourseLink(
                                    $course,
                                    $session,
                                    null,
                                    $linkVisibility,
                                    $insertDate,
                                    $editDate
                                )
                            );
                        }
                    }
                }

                $this->entityManager->persist($comment);
                ++$migrated;
                ++$pendingFlush;

                if ($pendingFlush >= self::FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $pendingFlush = 0;
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            $pendingFlush = 0;

            $this->getLogger()->info('Portfolio comment resource migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'skipped' => $skipped,
                'last_id' => $lastId,
            ]);
        }
    }

    /**
     * @param array<int, int|string> $refs
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadItemProperties(string $tool, array $refs): array
    {
        $refs = array_values(array_unique(array_map('intval', $refs)));
        if ([] === $refs) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            'SELECT * FROM c_item_property WHERE tool = :tool AND ref IN (:refs) ORDER BY ref, iid',
            ['tool' => $tool, 'refs' => $refs],
            ['refs' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['ref']][] = $row;
        }

        return $result;
    }
}
