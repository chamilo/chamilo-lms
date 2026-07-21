<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;

final class Version20250927180001 extends AbstractMigrationChamilo
{
    private const READ_BATCH_SIZE = 250;
    private const FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate portfolio items to resource nodes using keyset reads and bounded ORM flushes';
    }

    public function up(Schema $schema): void
    {
        /** @var PortfolioRepository $portfolioRepo */
        $portfolioRepo = $this->container->get(PortfolioRepository::class);

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
                    'SELECT * FROM portfolio WHERE id > :lastId ORDER BY id ASC LIMIT %d',
                    self::READ_BATCH_SIZE
                ),
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            $lastId = (int) $rows[array_key_last($rows)]['id'];
            $properties = $this->loadItemProperties('portfolio', array_column($rows, 'id'));

            foreach ($rows as $row) {
                ++$seen;
                $portfolioId = (int) $row['id'];
                $portfolio = $portfolioRepo->find($portfolioId);
                $creator = $userRepo->find((int) $row['user_id']);

                if (!$portfolio instanceof Portfolio || null === $creator || $portfolio->hasResourceNode()) {
                    ++$skipped;

                    continue;
                }

                $creationDate = new DateTime((string) $row['creation_date']);
                $updateDate = new DateTime((string) $row['update_date']);
                $course = !empty($row['c_id']) ? $this->findCourse((int) $row['c_id']) : null;
                $session = !empty($row['session_id']) ? $this->findSession((int) $row['session_id']) : null;

                $portfolio->setParent($creator);
                $resourceNode = $portfolioRepo->addResourceNode($portfolio, $creator, $creator);
                $resourceNode->setCreatedAt($creationDate)->setUpdatedAt($updateDate);
                $this->entityManager->persist($resourceNode);

                $resourceVisibility = match ($portfolio->getVisibility()) {
                    Portfolio::VISIBILITY_HIDDEN => ResourceLink::VISIBILITY_DRAFT,
                    Portfolio::VISIBILITY_VISIBLE => ResourceLink::VISIBILITY_PUBLISHED,
                    default => ResourceLink::VISIBILITY_PENDING,
                };

                $itemProperties = $properties[$portfolioId] ?? [];
                if ([] === $itemProperties && null !== $course) {
                    $this->entityManager->persist(
                        $portfolio->addCourseLink(
                            $course,
                            $session,
                            null,
                            $resourceVisibility,
                            $creationDate,
                            $updateDate
                        )
                    );
                } else {
                    foreach ($itemProperties as $itemProperty) {
                        $propertyCourse = !empty($itemProperty['c_id'])
                            ? $this->findCourse((int) $itemProperty['c_id'])
                            : null;
                        $propertySession = !empty($itemProperty['session_id'])
                            ? $this->findSession((int) $itemProperty['session_id'])
                            : null;
                        $toUser = !empty($itemProperty['to_user_id'])
                            ? $userRepo->find((int) $itemProperty['to_user_id'])
                            : null;
                        $insertDate = new DateTime((string) $itemProperty['insert_date'], new DateTimeZone('UTC'));
                        $editDate = new DateTime((string) $itemProperty['lastedit_date'], new DateTimeZone('UTC'));

                        $resourceNode->setUpdatedAt($editDate);
                        if (null !== $toUser) {
                            $this->entityManager->persist(
                                $portfolio->addUserLink(
                                    $toUser,
                                    $propertyCourse,
                                    $propertySession,
                                    null,
                                    $insertDate,
                                    $editDate
                                )
                            );
                        } elseif (null !== $propertyCourse) {
                            $this->entityManager->persist(
                                $portfolio->addCourseLink(
                                    $propertyCourse,
                                    $propertySession,
                                    null,
                                    $resourceVisibility,
                                    $insertDate,
                                    $editDate
                                )
                            );
                        }
                    }
                }

                $this->entityManager->persist($portfolio);
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

            $this->getLogger()->info('Portfolio resource migration progress.', [
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
