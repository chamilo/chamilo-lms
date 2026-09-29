<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\AdminStatistics;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserDeletedEvent;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserMergeHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class AdminStatisticsMaintenanceActionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private AccessUrlHelper $accessUrlHelper,
        private UserMergeHelper $userMergeHelper,
        private EventDispatcherInterface $eventDispatcher,
        private Security $security,
    ) {}

    /**
     * @param int[] $ids
     *
     * @return array{message: string, affectedCount: int}
     */
    public function runZombieAction(string $action, array $ids, string $ceiling, bool $activeOnly): array
    {
        $ids = $this->filterZombieIds($ids, $ceiling, $activeOnly);
        if ([] === $ids) {
            throw new BadRequestHttpException('No matching zombie users were found.');
        }

        $affectedCount = match ($action) {
            'activate' => $this->setActiveStatus($ids, User::ACTIVE, true),
            'deactivate' => $this->setActiveStatus($ids, User::INACTIVE, true),
            'delete' => $this->softDeleteUsers($ids),
            default => throw new BadRequestHttpException('Unsupported zombie-user action.'),
        };

        return [
            'message' => $affectedCount > 0 || 'delete' !== $action ? 'Update successful' : '',
            'affectedCount' => $affectedCount,
        ];
    }

    /**
     * @return array{message: string, affectedCount: int}
     */
    public function runDuplicateAction(
        string $action,
        int $userId,
        int $targetUserId,
        string $dupMode,
        int $extraFieldId,
    ): array {
        if (!\in_array($dupMode, ['name', 'email', 'extra'], true)) {
            $dupMode = 'name';
        }

        if ('activate' === $action || 'deactivate' === $action) {
            if ($userId <= 0) {
                throw new BadRequestHttpException('Invalid user identifier.');
            }
            if (!$this->isUserInDuplicateReport($dupMode, $userId, $extraFieldId)) {
                throw new BadRequestHttpException('No other duplicates found for this user.');
            }

            $active = 'activate' === $action ? User::ACTIVE : User::INACTIVE;
            $affected = $this->setActiveStatus([$userId], $active);

            return [
                'message' => User::ACTIVE === $active ? 'User enabled' : 'User deactivated',
                'affectedCount' => $affected,
            ];
        }

        if ('unify' !== $action) {
            throw new BadRequestHttpException('Unsupported duplicate-user action.');
        }
        if ($targetUserId <= 0) {
            throw new BadRequestHttpException('Invalid target user identifier.');
        }

        $groupIds = $this->getDuplicateGroupUserIds($dupMode, $targetUserId, $extraFieldId);
        if (\count($groupIds) < 2) {
            throw new BadRequestHttpException('No other duplicates found for this user.');
        }

        $mergeIds = array_values(array_filter(
            $groupIds,
            static fn (int $id): bool => $targetUserId !== $id
        ));

        try {
            $mergedCount = $this->userMergeHelper->mergeUsersBatch($targetUserId, $mergeIds, null, true);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException('An error occurred while unifying users.', $exception);
        }

        if ($mergedCount <= 0) {
            throw new BadRequestHttpException('No accounts were merged.');
        }

        return [
            'message' => 'Users unified: merged '.$mergedCount.' account(s) into user #'.$targetUserId
                .'. Merged accounts were permanently deleted.',
            'affectedCount' => $mergedCount,
        ];
    }

    /**
     * @param int[] $ids
     */
    private function setActiveStatus(array $ids, int $active, bool $logEvent = false): int
    {
        $ids = $this->normalizeIds($ids);
        if ([] === $ids) {
            return 0;
        }

        $affected = $this->entityManager->getConnection()->executeStatement(
            'UPDATE user SET active = :active WHERE id IN (:ids) AND active <> :softDeleted',
            [
                'active' => $active,
                'ids' => $ids,
                'softDeleted' => User::SOFT_DELETED,
            ],
            [
                'active' => Types::INTEGER,
                'ids' => ArrayParameterType::INTEGER,
                'softDeleted' => Types::INTEGER,
            ]
        );

        if ($logEvent) {
            $this->logUserStatusEvent($ids, $active);
        }

        return $affected;
    }

    /**
     * @param int[] $ids
     */
    private function logUserStatusEvent(array $ids, int $active): void
    {
        $actor = $this->security->getUser();
        $actorId = $actor instanceof User && null !== $actor->getId() ? (int) $actor->getId() : 0;

        $event = new TrackEDefault();
        $event->setDefaultUserId($actorId);
        $event->setCId(0);
        $event->setDefaultDate(new DateTime('now', new DateTimeZone('UTC')));
        $event->setDefaultEventType(User::ACTIVE === $active ? 'user_enable' : 'user_disable');
        $event->setDefaultValueType('user_id');
        $event->setDefaultValue(implode(',', array_map('strval', $ids)));
        $event->setSessionId(0);

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    /**
     * @param int[] $ids
     */
    private function softDeleteUsers(array $ids): int
    {
        if ($this->denyDeleteUsers()) {
            return 0;
        }

        $affected = 0;
        foreach ($ids as $id) {
            if (!$this->canDeleteUser($id)) {
                continue;
            }

            $user = $this->userRepository->find($id);
            if (!$user instanceof User || User::SOFT_DELETED === $user->getActive()) {
                continue;
            }

            $this->eventDispatcher->dispatch(
                new UserDeletedEvent(
                    ['user' => $user, 'deleteType' => UserDeletedEvent::DELETE_TYPE_SOFT],
                    AbstractEvent::TYPE_PRE
                ),
                Events::USER_DELETED
            );
            $this->userRepository->deleteUser($user, false);
            $this->eventDispatcher->dispatch(
                new UserDeletedEvent(
                    ['deleteType' => UserDeletedEvent::DELETE_TYPE_SOFT, 'user' => $user],
                    AbstractEvent::TYPE_POST
                ),
                Events::USER_DELETED
            );
            ++$affected;
        }

        return $affected;
    }

    private function canDeleteUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $connection = $this->entityManager->getConnection();
        $courseIds = $connection->executeQuery(
            'SELECT c_id FROM course_rel_user WHERE status = :teacher AND user_id = :userId',
            ['teacher' => 1, 'userId' => $userId],
            ['teacher' => Types::INTEGER, 'userId' => Types::INTEGER]
        )->fetchFirstColumn();

        foreach ($courseIds as $courseId) {
            $teachers = (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM course_rel_user WHERE status = :teacher AND c_id = :courseId',
                ['teacher' => 1, 'courseId' => (int) $courseId],
                ['teacher' => Types::INTEGER, 'courseId' => Types::INTEGER]
            );
            if (1 === $teachers) {
                return false;
            }
        }

        return true;
    }

    private function denyDeleteUsers(): bool
    {
        $value = $_SERVER['DENY_DELETE_USERS'] ?? $_ENV['DENY_DELETE_USERS'] ?? getenv('DENY_DELETE_USERS');
        if (false === $value) {
            return false;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    private function filterZombieIds(array $ids, string $ceiling, bool $activeOnly): array
    {
        $ids = $this->normalizeIds($ids);
        if ([] === $ids) {
            return [];
        }

        $ceilingDate = DateTimeImmutable::createFromFormat('!Y-m-d', trim($ceiling));
        if (!$ceilingDate instanceof DateTimeImmutable) {
            $ceilingDate = new DateTimeImmutable('today');
        }

        $connection = $this->entityManager->getConnection();
        [$joinUrl, $urlCondition, $urlParameters, $urlTypes] = $this->getUserAccessUrlSql('u', 'url');
        $where = 'access.login_date = (SELECT MAX(a.login_date) FROM track_e_login a WHERE a.login_user_id = u.id) '
            .'AND access.login_date <= :ceiling AND u.id = access.login_user_id '
            .$urlCondition.' AND u.active <> :softDeleted AND u.id IN (:ids)';

        $parameters = [
            ...$urlParameters,
            'ceiling' => $ceilingDate->format('Y-m-d').' 00:00:00',
            'softDeleted' => User::SOFT_DELETED,
            'ids' => $ids,
        ];
        $types = [
            ...$urlTypes,
            'ceiling' => Types::STRING,
            'softDeleted' => Types::INTEGER,
            'ids' => ArrayParameterType::INTEGER,
        ];

        if ($activeOnly) {
            $where .= ' AND u.active = :active';
            $parameters['active'] = User::ACTIVE;
            $types['active'] = Types::INTEGER;
        }

        $validIds = $connection->executeQuery(
            'SELECT DISTINCT u.id FROM user u INNER JOIN track_e_login access ON access.login_user_id = u.id '
            .$joinUrl.' WHERE '.$where,
            $parameters,
            $types,
        )->fetchFirstColumn();

        return $this->normalizeIds(array_map(static fn (mixed $id): int => (int) $id, $validIds));
    }

    private function isUserInDuplicateReport(string $dupMode, int $userId, int $extraFieldId): bool
    {
        $connection = $this->entityManager->getConnection();
        $target = $connection->executeQuery(
            'SELECT id, firstname, lastname, email FROM user WHERE id = :userId AND active <> :softDeleted LIMIT 1',
            ['userId' => $userId, 'softDeleted' => User::SOFT_DELETED],
            ['userId' => Types::INTEGER, 'softDeleted' => Types::INTEGER],
        )->fetchAssociative();
        if (false === $target) {
            return false;
        }

        $parameters = ['softDeleted' => User::SOFT_DELETED];
        $types = ['softDeleted' => Types::INTEGER];

        if ('name' === $dupMode) {
            $firstname = mb_strtolower(trim((string) ($target['firstname'] ?? '')), 'UTF-8');
            $lastname = mb_strtolower(trim((string) ($target['lastname'] ?? '')), 'UTF-8');
            if ('' === $firstname && '' === $lastname) {
                return false;
            }

            $parameters['firstname'] = $firstname;
            $parameters['lastname'] = $lastname;
            $types['firstname'] = Types::STRING;
            $types['lastname'] = Types::STRING;

            $count = (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE active <> :softDeleted '
                .'AND LOWER(TRIM(COALESCE(firstname, \'\'))) = :firstname '
                .'AND LOWER(TRIM(COALESCE(lastname, \'\'))) = :lastname',
                $parameters,
                $types,
            );

            return $count > 1;
        }

        if ('email' === $dupMode) {
            $email = mb_strtolower(trim((string) ($target['email'] ?? '')), 'UTF-8');
            if ('' === $email) {
                return false;
            }

            $parameters['email'] = $email;
            $types['email'] = Types::STRING;

            $count = (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE active <> :softDeleted '
                .'AND TRIM(COALESCE(email, \'\')) <> \'\' '
                .'AND LOWER(TRIM(COALESCE(email, \'\'))) = :email',
                $parameters,
                $types,
            );

            return $count > 1;
        }

        if ($extraFieldId <= 0) {
            return false;
        }

        $fieldValue = $connection->executeQuery(
            'SELECT TRIM(COALESCE(field_value, \'\')) FROM extra_field_values '
            .'WHERE item_id = :userId AND field_id = :extraFieldId LIMIT 1',
            ['userId' => $userId, 'extraFieldId' => $extraFieldId],
            ['userId' => Types::INTEGER, 'extraFieldId' => Types::INTEGER],
        )->fetchOne();
        $fieldValue = mb_strtolower(trim((string) $fieldValue), 'UTF-8');
        if ('' === $fieldValue) {
            return false;
        }

        $count = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM extra_field_values v INNER JOIN user u ON u.id = v.item_id '
            .'WHERE v.field_id = :extraFieldId AND u.active <> :softDeleted '
            .'AND TRIM(COALESCE(v.field_value, \'\')) <> \'\' '
            .'AND LOWER(TRIM(COALESCE(v.field_value, \'\'))) = :fieldValue',
            [
                'extraFieldId' => $extraFieldId,
                'softDeleted' => User::SOFT_DELETED,
                'fieldValue' => $fieldValue,
            ],
            [
                'extraFieldId' => Types::INTEGER,
                'softDeleted' => Types::INTEGER,
                'fieldValue' => Types::STRING,
            ],
        );

        return $count > 1;
    }

    /**
     * @return int[]
     */
    private function getDuplicateGroupUserIds(string $dupMode, int $targetUserId, int $extraFieldId): array
    {
        $connection = $this->entityManager->getConnection();
        [$joinUrl, $urlCondition, $urlParameters, $urlTypes] = $this->getUserAccessUrlSql('u', 'url');
        $target = $connection->executeQuery(
            'SELECT u.id, u.firstname, u.lastname, u.email FROM user u '.$joinUrl
            .' WHERE u.id = :targetUserId AND u.active <> :softDeleted '.$urlCondition.' LIMIT 1',
            [...$urlParameters, 'targetUserId' => $targetUserId, 'softDeleted' => User::SOFT_DELETED],
            [...$urlTypes, 'targetUserId' => Types::INTEGER, 'softDeleted' => Types::INTEGER]
        )->fetchAssociative();
        if (false === $target) {
            return [];
        }

        $parameters = [...$urlParameters, 'softDeleted' => User::SOFT_DELETED];
        $types = [...$urlTypes, 'softDeleted' => Types::INTEGER];

        if ('name' === $dupMode) {
            $firstname = mb_strtolower(trim((string) ($target['firstname'] ?? '')), 'UTF-8');
            $lastname = mb_strtolower(trim((string) ($target['lastname'] ?? '')), 'UTF-8');
            if ('' === $firstname && '' === $lastname) {
                return [];
            }
            $parameters['firstname'] = $firstname;
            $parameters['lastname'] = $lastname;
            $types['firstname'] = Types::STRING;
            $types['lastname'] = Types::STRING;
            $sql = 'SELECT u.id FROM user u '.$joinUrl.' WHERE u.active <> :softDeleted '.$urlCondition
                .' AND LOWER(TRIM(COALESCE(u.firstname, \'\'))) = :firstname'
                .' AND LOWER(TRIM(COALESCE(u.lastname, \'\'))) = :lastname ORDER BY u.id ASC';
        } elseif ('email' === $dupMode) {
            $email = mb_strtolower(trim((string) ($target['email'] ?? '')), 'UTF-8');
            if ('' === $email) {
                return [];
            }
            $parameters['email'] = $email;
            $types['email'] = Types::STRING;
            $sql = 'SELECT u.id FROM user u '.$joinUrl.' WHERE u.active <> :softDeleted '.$urlCondition
                .' AND TRIM(COALESCE(u.email, \'\')) <> \'\''
                .' AND LOWER(TRIM(COALESCE(u.email, \'\'))) = :email ORDER BY u.id ASC';
        } else {
            if ($extraFieldId <= 0) {
                return [];
            }
            $value = $connection->executeQuery(
                'SELECT TRIM(COALESCE(v.field_value, \'\')) AS value FROM extra_field_values v '
                .'INNER JOIN user u ON u.id = v.item_id '.$joinUrl
                .' WHERE u.id = :targetUserId AND u.active <> :softDeleted '.$urlCondition
                .' AND v.field_id = :extraFieldId LIMIT 1',
                [
                    ...$urlParameters,
                    'targetUserId' => $targetUserId,
                    'softDeleted' => User::SOFT_DELETED,
                    'extraFieldId' => $extraFieldId,
                ],
                [
                    ...$urlTypes,
                    'targetUserId' => Types::INTEGER,
                    'softDeleted' => Types::INTEGER,
                    'extraFieldId' => Types::INTEGER,
                ]
            )->fetchOne();
            $normalizedValue = mb_strtolower(trim((string) $value), 'UTF-8');
            if ('' === $normalizedValue) {
                return [];
            }
            $parameters['extraFieldId'] = $extraFieldId;
            $parameters['fieldValue'] = $normalizedValue;
            $types['extraFieldId'] = Types::INTEGER;
            $types['fieldValue'] = Types::STRING;
            $sql = 'SELECT u.id FROM user u INNER JOIN extra_field_values v '
                .'ON v.item_id = u.id AND v.field_id = :extraFieldId '.$joinUrl
                .' WHERE u.active <> :softDeleted '.$urlCondition
                .' AND TRIM(COALESCE(v.field_value, \'\')) <> \'\''
                .' AND LOWER(TRIM(COALESCE(v.field_value, \'\'))) = :fieldValue ORDER BY u.id ASC';
        }

        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            $connection->executeQuery($sql, $parameters, $types)->fetchFirstColumn()
        );
        $ids = $this->normalizeIds($ids);

        return \count($ids) >= 2 ? $ids : [];
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, int>, 3: array<string, string>}
     */
    private function getUserAccessUrlSql(string $userAlias, string $urlAlias): array
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return ['', '', [], []];
        }

        $current = $this->accessUrlHelper->getCurrent();
        if (null === $current || null === $current->getId()) {
            throw new NotFoundHttpException('The current access URL could not be resolved.');
        }

        return [
            ' INNER JOIN access_url_rel_user '.$urlAlias.' ON '.$urlAlias.'.user_id = '.$userAlias.'.id ',
            ' AND '.$urlAlias.'.access_url_id = :accessUrlId ',
            ['accessUrlId' => (int) $current->getId()],
            ['accessUrlId' => Types::INTEGER],
        ];
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0
        )));
    }
}
