<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20230904173400 extends AbstractMigrationChamilo
{
    private const AGENDA_BATCH_SIZE = 250;
    private const MAP_TABLE = 'tmp_ricky_personal_agenda_map';
    private const PARENT_INDEX = 'idx_ricky_personal_agenda_parent_event';

    public function getDescription(): string
    {
        return 'Migrate personal_agenda to c_calendar_event in bounded ORM batches and update agenda_reminder';
    }

    public function up(Schema $schema): void
    {
        $this->ensurePersonalAgendaParentIndex();

        $collectiveInvitationsEnabled = $this->isEnabledSetting(
            $this->getConfigurationValue('agenda_collective_invitations')
        );
        $subscriptionsEnabled = $this->isEnabledSetting(
            $this->getConfigurationValue('agenda_event_subscriptions')
        );

        // These statements must run before reading personal_agenda. Using the
        // connection directly avoids deferring them until after the ORM work.
        $this->connection->executeStatement(
            "UPDATE personal_agenda SET parent_event_id = NULL WHERE parent_event_id = 0 OR parent_event_id = ''"
        );
        $this->connection->executeStatement(
            'UPDATE personal_agenda pa
             LEFT JOIN personal_agenda parent ON parent.id = pa.parent_event_id
             SET pa.parent_event_id = NULL
             WHERE pa.parent_event_id IS NOT NULL AND parent.id IS NULL'
        );
        $this->connection->executeStatement(
            'DELETE pa FROM personal_agenda pa LEFT JOIN user u ON u.id = pa.user WHERE u.id IS NULL'
        );

        $this->connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS '.self::MAP_TABLE);
        $this->connection->executeStatement(
            'CREATE TEMPORARY TABLE '.self::MAP_TABLE.' (
                old_id INT NOT NULL PRIMARY KEY,
                new_id INT NOT NULL,
                KEY idx_new_id (new_id)
            ) ENGINE=InnoDB'
        );

        $total = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM personal_agenda');
        $migrated = 0;
        $startedAt = microtime(true);

        // First migrate root events. Their resource nodes are parented to the
        // creator, so all rows in a batch can be flushed together safely.
        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    'SELECT pa.*, NULL AS new_parent_id
                     FROM personal_agenda pa
                     LEFT JOIN %s mapped ON mapped.old_id = pa.id
                     WHERE mapped.old_id IS NULL
                       AND pa.parent_event_id IS NULL
                     ORDER BY pa.id
                     LIMIT %d',
                    self::MAP_TABLE,
                    self::AGENDA_BATCH_SIZE
                )
            );

            if ([] === $rows) {
                break;
            }

            $migrated += $this->persistBatch(
                $rows,
                $collectiveInvitationsEnabled,
                $subscriptionsEnabled
            );
            $this->logProgress($migrated, $total, $startedAt, 'roots');
        }

        // Then repeatedly migrate rows whose parent has already been mapped.
        // This preserves arbitrarily deep hierarchies without one flush per row.
        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    'SELECT pa.*, parent_map.new_id AS new_parent_id
                     FROM personal_agenda pa
                     INNER JOIN %1$s parent_map ON parent_map.old_id = pa.parent_event_id
                     LEFT JOIN %1$s mapped ON mapped.old_id = pa.id
                     WHERE mapped.old_id IS NULL
                     ORDER BY pa.id
                     LIMIT %2$d',
                    self::MAP_TABLE,
                    self::AGENDA_BATCH_SIZE
                )
            );

            if ([] === $rows) {
                break;
            }

            $migrated += $this->persistBatch(
                $rows,
                $collectiveInvitationsEnabled,
                $subscriptionsEnabled
            );
            $this->logProgress($migrated, $total, $startedAt, 'children');
        }

        // Cycles are not valid agenda hierarchies. Preserve their events as
        // roots instead of losing them or looping forever.
        $unresolved = (int) $this->connection->fetchOne(
            \sprintf(
                'SELECT COUNT(*)
                 FROM personal_agenda pa
                 LEFT JOIN %s mapped ON mapped.old_id = pa.id
                 WHERE mapped.old_id IS NULL',
                self::MAP_TABLE
            )
        );

        if ($unresolved > 0) {
            $this->getLogger()->warning('Personal agenda contains unresolved/cyclic parent links; preserving those events as roots.', [
                'count' => $unresolved,
            ]);

            while (true) {
                $rows = $this->connection->fetchAllAssociative(
                    \sprintf(
                        'SELECT pa.*, NULL AS new_parent_id
                         FROM personal_agenda pa
                         LEFT JOIN %s mapped ON mapped.old_id = pa.id
                         WHERE mapped.old_id IS NULL
                         ORDER BY pa.id
                         LIMIT %d',
                        self::MAP_TABLE,
                        self::AGENDA_BATCH_SIZE
                    )
                );

                if ([] === $rows) {
                    break;
                }

                $migrated += $this->persistBatch(
                    $rows,
                    $collectiveInvitationsEnabled,
                    $subscriptionsEnabled
                );
                $this->logProgress($migrated, $total, $startedAt, 'unresolved-as-roots');
            }
        }

        if ($schema->hasTable('agenda_reminder')) {
            $agendaReminder = $schema->getTable('agenda_reminder');
            if ($agendaReminder->hasColumn('type')) {
                $updated = $this->connection->executeStatement(
                    \sprintf(
                        "UPDATE agenda_reminder reminder
                         INNER JOIN %s mapped ON mapped.old_id = reminder.event_id
                         SET reminder.event_id = mapped.new_id
                         WHERE reminder.type = 'personal'",
                        self::MAP_TABLE
                    )
                );

                $this->getLogger()->info('Personal agenda reminders updated.', ['updated' => $updated]);
            }
        }

        $this->connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS '.self::MAP_TABLE);

        $this->getLogger()->info('Personal agenda migration completed.', [
            'total' => $total,
            'migrated' => $migrated,
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistBatch(
        array $rows,
        bool $collectiveInvitationsEnabled,
        bool $subscriptionsEnabled
    ): int {
        $agendaIds = [];
        $creatorIds = [];
        $parentEventIds = [];

        foreach ($rows as $row) {
            $agendaIds[] = (int) $row['id'];
            $creatorIds[] = (int) $row['user'];
            if (!empty($row['new_parent_id'])) {
                $parentEventIds[] = (int) $row['new_parent_id'];
            }
        }

        $invitationData = $collectiveInvitationsEnabled
            ? $this->loadInvitationData($agendaIds, $subscriptionsEnabled)
            : ['by_agenda' => [], 'users_by_invitation' => []];

        $allUserIds = $creatorIds;
        foreach ($invitationData['users_by_invitation'] as $userIds) {
            foreach ($userIds as $userId) {
                $allUserIds[] = (int) $userId;
            }
        }

        $users = $this->loadUsers($allUserIds);
        $parents = $this->loadParentEvents($parentEventIds);
        $utc = new DateTimeZone('UTC');
        $entitiesByOldId = [];

        foreach ($rows as $row) {
            $oldId = (int) $row['id'];
            $creatorId = (int) $row['user'];
            $creator = $users[$creatorId] ?? null;

            if (!$creator instanceof User) {
                throw new RuntimeException("Creator {$creatorId} was not found for personal agenda {$oldId}.");
            }

            $parent = null;
            if (!empty($row['new_parent_id'])) {
                $parentId = (int) $row['new_parent_id'];
                $parent = $parents[$parentId] ?? null;
                if (!$parent instanceof CCalendarEvent) {
                    throw new RuntimeException("Migrated parent event {$parentId} was not found for personal agenda {$oldId}.");
                }
            }

            $event = $this->createCalendarEvent($row, $creator, $parent, $utc);
            $this->applyInvitationData(
                $event,
                $row,
                $invitationData,
                $users,
                $subscriptionsEnabled
            );

            $this->entityManager->persist($event);
            $entitiesByOldId[$oldId] = $event;
        }

        $this->entityManager->flush();

        $mappingRows = [];
        foreach ($entitiesByOldId as $oldId => $event) {
            $newId = (int) $event->getIid();
            if ($newId <= 0) {
                throw new RuntimeException("No target IID was generated for personal agenda {$oldId}.");
            }
            $mappingRows[] = ['old_id' => (int) $oldId, 'new_id' => $newId];
        }

        $this->insertMappings($mappingRows);
        $this->entityManager->clear();

        return \count($mappingRows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function createCalendarEvent(
        array $row,
        User $creator,
        ?CCalendarEvent $parent,
        DateTimeZone $utc
    ): CCalendarEvent {
        $event = new CCalendarEvent();
        $title = '' !== trim((string) ($row['title'] ?? '')) ? (string) $row['title'] : '-';

        $event
            ->setTitle($title)
            ->setContent((string) ($row['text'] ?? ''))
            ->setStartDate(!empty($row['date']) ? new DateTime((string) $row['date'], $utc) : null)
            ->setEndDate(!empty($row['enddate']) ? new DateTime((string) $row['enddate'], $utc) : null)
            ->setAllDay((bool) ($row['all_day'] ?? false))
            ->setColor((string) ($row['color'] ?? ''))
            ->setCreator($creator)
            ->setResourceName($title)
        ;

        if ($parent instanceof CCalendarEvent) {
            $event
                ->setParentEvent($parent)
                ->setParentResourceNode($parent->getResourceNode()->getId())
            ;
        } else {
            $event->setParentResourceNode($creator->getResourceNode()->getId());
        }

        return $event;
    }

    /**
     * @param array<string, mixed>                                  $row
     * @param array{by_agenda: array<int, array<string, mixed>>, users_by_invitation: array<int, array<int, int>>} $invitationData
     * @param array<int, User>                                      $users
     */
    private function applyInvitationData(
        CCalendarEvent $event,
        array $row,
        array $invitationData,
        array $users,
        bool $subscriptionsEnabled
    ): void {
        $agendaId = (int) $row['id'];
        $invitation = $invitationData['by_agenda'][$agendaId] ?? null;
        if (!\is_array($invitation)) {
            return;
        }

        $type = (string) ($invitation['type'] ?? '');
        $isSubscription = $subscriptionsEnabled
            && 'subscription' === $type
            && 0 !== (int) ($row['subscription_visibility'] ?? 0);
        $isInvitation = (!$subscriptionsEnabled || 'invitation' === $type);

        if (!$isSubscription && !$isInvitation) {
            return;
        }

        if ($isSubscription) {
            $event
                ->setInvitationType(CCalendarEvent::TYPE_SUBSCRIPTION)
                ->setSubscriptionVisibility((int) ($row['subscription_visibility'] ?? 0))
                ->setSubscriptionItemId(
                    !empty($row['subscription_item_id']) ? (int) $row['subscription_item_id'] : null
                )
                ->setMaxAttendees((int) ($invitation['max_attendees'] ?? 0))
            ;
        } else {
            $event
                ->setCollective((bool) ($row['collective'] ?? false))
                ->setInvitationType(CCalendarEvent::TYPE_INVITATION)
            ;
        }

        $invitationId = (int) $invitation['id'];
        $added = [];
        foreach ($invitationData['users_by_invitation'][$invitationId] ?? [] as $userId) {
            $userId = (int) $userId;
            if (isset($added[$userId])) {
                continue;
            }
            $user = $users[$userId] ?? null;
            if ($user instanceof User) {
                $event->addUserLink($user);
                $added[$userId] = true;
            }
        }
    }

    /**
     * @param array<int, int> $agendaIds
     *
     * @return array{by_agenda: array<int, array<string, mixed>>, users_by_invitation: array<int, array<int, int>>}
     */
    private function loadInvitationData(array $agendaIds, bool $subscriptionsEnabled): array
    {
        if ([] === $agendaIds) {
            return ['by_agenda' => [], 'users_by_invitation' => []];
        }

        try {
            $rows = $this->connection->executeQuery(
                'SELECT pa.id AS agenda_id, invitation.id, invitation.type, invitation.max_attendees
                 FROM personal_agenda pa
                 INNER JOIN agenda_event_invitation invitation
                    ON invitation.id = pa.agenda_event_invitation_id
                 WHERE pa.id IN (:ids)',
                ['ids' => $agendaIds],
                ['ids' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
        } catch (Throwable) {
            return ['by_agenda' => [], 'users_by_invitation' => []];
        }

        $byAgenda = [];
        $invitationIds = [];
        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');
            if ($subscriptionsEnabled && !\in_array($type, ['invitation', 'subscription'], true)) {
                continue;
            }
            $agendaId = (int) $row['agenda_id'];
            $byAgenda[$agendaId] = $row;
            $invitationIds[] = (int) $row['id'];
        }

        if ([] === $invitationIds) {
            return ['by_agenda' => $byAgenda, 'users_by_invitation' => []];
        }

        try {
            $inviteeRows = $this->connection->executeQuery(
                'SELECT invitation_id, user_id
                 FROM agenda_event_invitee
                 WHERE invitation_id IN (:ids)
                 ORDER BY invitation_id, created_at, id',
                ['ids' => \array_values(\array_unique($invitationIds))],
                ['ids' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
        } catch (Throwable) {
            $inviteeRows = [];
        }

        $usersByInvitation = [];
        foreach ($inviteeRows as $row) {
            $usersByInvitation[(int) $row['invitation_id']][] = (int) $row['user_id'];
        }

        return ['by_agenda' => $byAgenda, 'users_by_invitation' => $usersByInvitation];
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, User>
     */
    private function loadUsers(array $userIds): array
    {
        $userIds = \array_values(\array_unique(\array_filter(\array_map('intval', $userIds))));
        if ([] === $userIds) {
            return [];
        }

        $entities = $this->entityManager
            ->createQuery('SELECT user FROM Chamilo\\CoreBundle\\Entity\\User user WHERE user.id IN (:ids)')
            ->setParameter('ids', $userIds)
            ->getResult()
        ;

        $result = [];
        foreach ($entities as $entity) {
            if ($entity instanceof User) {
                $result[(int) $entity->getId()] = $entity;
            }
        }

        return $result;
    }

    /**
     * @param array<int, int> $eventIds
     *
     * @return array<int, CCalendarEvent>
     */
    private function loadParentEvents(array $eventIds): array
    {
        $eventIds = \array_values(\array_unique(\array_filter(\array_map('intval', $eventIds))));
        if ([] === $eventIds) {
            return [];
        }

        $entities = $this->entityManager
            ->createQuery(
                'SELECT event
                 FROM Chamilo\CourseBundle\Entity\CCalendarEvent event
                 WHERE event.iid IN (:ids)'
            )
            ->setParameter('ids', $eventIds)
            ->getResult()
        ;

        $result = [];
        foreach ($entities as $event) {
            if ($event instanceof CCalendarEvent) {
                $result[(int) $event->getIid()] = $event;
            }
        }

        return $result;
    }

    /**
     * @param array<int, array{old_id: int, new_id: int}> $rows
     */
    private function insertMappings(array $rows): void
    {
        if ([] === $rows) {
            return;
        }

        $values = [];
        $parameters = [];
        foreach ($rows as $index => $row) {
            $values[] = "(:old_{$index}, :new_{$index})";
            $parameters["old_{$index}"] = $row['old_id'];
            $parameters["new_{$index}"] = $row['new_id'];
        }

        $this->connection->executeStatement(
            'INSERT INTO '.self::MAP_TABLE.' (old_id, new_id) VALUES '.\implode(', ', $values),
            $parameters
        );
    }


    private function ensurePersonalAgendaParentIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('personal_agenda', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('personal_agenda') as $index) {
                $columns = array_map('strtolower', $index->getColumns());
                if ([] !== $columns && 'parent_event_id' === $columns[0]) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary personal-agenda parent index.', [
                'index' => self::PARENT_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::PARENT_INDEX.' ON personal_agenda (parent_event_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create personal-agenda parent index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isEnabledSetting(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function logProgress(int $migrated, int $total, float $startedAt, string $phase): void
    {
        $elapsed = max(1, (int) (microtime(true) - $startedAt));
        $rate = $migrated / $elapsed;
        $remaining = max(0, $total - $migrated);

        $this->getLogger()->info('Personal agenda migration progress.', [
            'phase' => $phase,
            'migrated' => $migrated,
            'total' => $total,
            'rows_per_second' => round($rate, 2),
            'eta_seconds' => $rate > 0 ? (int) round($remaining / $rate) : null,
        ]);
    }
}
