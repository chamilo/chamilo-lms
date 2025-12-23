<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Admin;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Entity\UserRelTag;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Helpers\QueryCacheHelper;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Datetime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const MB_CASE_LOWER;

/**
 * @extends ResourceRepository<User>
 */
class UserRepository extends ResourceRepository implements PasswordUpgraderInterface
{
    protected ?UserPasswordHasherInterface $hasher = null;

    public const USER_IMAGE_SIZE_SMALL = 1;
    public const USER_IMAGE_SIZE_MEDIUM = 2;
    public const USER_IMAGE_SIZE_BIG = 3;
    public const USER_IMAGE_SIZE_ORIGINAL = 4;

    public function __construct(
        ManagerRegistry $registry,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly TranslatorInterface $translator,
        private readonly QueryCacheHelper $queryCacheHelper
    ) {
        parent::__construct($registry, User::class);
    }

    public function isUsernameAvailable(string $username): bool
    {
        return 0 === $this->count(['username' => $username]);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneBy([
            'username' => $identifier,
        ]);
    }

    public function setHasher(UserPasswordHasherInterface $hasher): void
    {
        $this->hasher = $hasher;
    }

    public function createUser(): User
    {
        return new User();
    }

    public function updateUser(User $user, bool $andFlush = true): void
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);
        $this->getEntityManager()->persist($user);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function canonicalize(string $string): string
    {
        $encoding = mb_detect_encoding($string, mb_detect_order(), true);

        return $encoding
            ? mb_convert_case($string, MB_CASE_LOWER, $encoding)
            : mb_convert_case($string, MB_CASE_LOWER);
    }

    public function updateCanonicalFields(User $user): void
    {
        $user->setUsernameCanonical($this->canonicalize($user->getUsername()));
        $user->setEmailCanonical($this->canonicalize($user->getEmail()));
    }

    public function updatePassword(User $user): void
    {
        $password = (string) $user->getPlainPassword();
        if ('' !== $password) {
            $password = $this->hasher->hashPassword($user, $password);
            $user->setPassword($password);
            $user->eraseCredentials();
        }
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->hasher->isPasswordValid($user, $plainPassword);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        /** @var User $user */
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function getRootUser(): User
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->innerJoin(
                'u.resourceNode',
                'r'
            )
        ;
        $qb
            ->where('r.creator = u')
            ->andWhere('r.parent IS NULL')
            ->getFirstResult()
        ;

        $rootUser = $qb->getQuery()->getSingleResult();

        if (null === $rootUser) {
            throw new UserNotFoundException('Root user not found');
        }

        return $rootUser;
    }

    public function deleteUser(User $user, bool $destroy = false): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            if ($destroy) {
                // Call method to delete messages and attachments
                $this->deleteUserMessagesAndAttachments($user);

                $fallbackUser = $this->getFallbackUser();

                if ($fallbackUser) {
                    $this->reassignUserResourcesToFallbackSQL($user, $fallbackUser, $connection);
                }

                // Remove extra fields
                $connection->executeStatement(
                    'DELETE v FROM extra_field_values v
                INNER JOIN extra_field f ON f.id = v.field_id
                WHERE f.item_type = :userType AND v.item_id = :uid',
                    ['userType' => ExtraField::USER_FIELD_TYPE, 'uid' => $user->getId()]
                );

                $connection->executeStatement(
                    'DELETE r FROM extra_field_rel_tag r
                INNER JOIN extra_field f ON f.id = r.field_id
                WHERE f.item_type = :userType AND r.item_id = :uid',
                    ['userType' => ExtraField::USER_FIELD_TYPE, 'uid' => $user->getId()]
                );

                // Remove group relationships
                $connection->executeStatement(
                    'DELETE FROM usergroup_rel_user WHERE user_id = :userId',
                    ['userId' => $user->getId()]
                );

                // Remove resource node if exists
                $connection->executeStatement(
                    'DELETE FROM resource_node WHERE id = :nodeId',
                    ['nodeId' => $user->getResourceNode()->getId()]
                );

                // Remove the user itself
                $connection->executeStatement(
                    'DELETE FROM user WHERE id = :userId',
                    ['userId' => $user->getId()]
                );
            } else {
                // Soft delete the user
                $connection->executeStatement(
                    'UPDATE user SET active = :softDeleted WHERE id = :userId',
                    ['softDeleted' => User::SOFT_DELETED, 'userId' => $user->getId()]
                );
            }

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    /**
     * Reassigns resources and related data from a deleted user to a fallback user in the database.
     *
     * @param mixed $connection
     */
    protected function reassignUserResourcesToFallbackSQL(User $userToDelete, User $fallbackUser, $connection): void
    {
        // Update resource nodes created by the user
        $connection->executeStatement(
            'UPDATE resource_node SET creator_id = :fallbackUserId WHERE creator_id = :userId',
            ['fallbackUserId' => $fallbackUser->getId(), 'userId' => $userToDelete->getId()]
        );

        // Update child resource nodes
        $connection->executeStatement(
            'UPDATE resource_node SET parent_id = :fallbackParentId WHERE parent_id = :userParentId',
            [
                'fallbackParentId' => $fallbackUser->getResourceNode()?->getId(),
                'userParentId' => $userToDelete->getResourceNode()->getId(),
            ]
        );

        // Relations to update or delete
        $relations = $this->getRelations();

        foreach ($relations as $relation) {
            $table = $relation['table'];
            $field = $relation['field'];
            $action = $relation['action'];

            if ('delete' === $action) {
                $connection->executeStatement(
                    "DELETE FROM $table WHERE $field = :userId",
                    ['userId' => $userToDelete->getId()]
                );
            } elseif ('update' === $action) {
                $connection->executeStatement(
                    "UPDATE $table SET $field = :fallbackUserId WHERE $field = :userId",
                    [
                        'fallbackUserId' => $fallbackUser->getId(),
                        'userId' => $userToDelete->getId(),
                    ]
                );
            }
        }
    }

    /**
     * Provides a list of database table relations and their respective actions
     * (update or delete) for handling user resource reassignment or deletion.
     *
     * Any new database table that stores references to users and requires updates
     * or deletions when a user is removed should be added to this list. This ensures
     * proper handling of dependencies and avoids orphaned data.
     */
    protected function getRelations(): array
    {
        return [
            ['table' => 'access_url_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'admin', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'attempt_feedback', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'chat', 'field' => 'to_user', 'action' => 'update'],
            ['table' => 'chat_video', 'field' => 'to_user', 'action' => 'update'],
            ['table' => 'course_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'course_rel_user_catalogue', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'course_request', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_attendance_result', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_attendance_result_comment', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_attendance_sheet', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_attendance_sheet_log', 'field' => 'lastedit_user_id', 'action' => 'delete'],
            ['table' => 'c_chat_connected', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_dropbox_category', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_dropbox_feedback', 'field' => 'author_user_id', 'action' => 'update'],
            ['table' => 'c_dropbox_person', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_dropbox_post', 'field' => 'dest_user_id', 'action' => 'update'],
            ['table' => 'c_forum_mailcue', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_forum_notification', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_forum_post', 'field' => 'poster_id', 'action' => 'update'],
            ['table' => 'c_forum_thread', 'field' => 'thread_poster_id', 'action' => 'update'],
            ['table' => 'c_forum_thread_qualify', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_forum_thread_qualify_log', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_group_rel_tutor', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_group_rel_user', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_lp_category_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_lp_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_lp_view', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_student_publication_comment', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_student_publication_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'c_survey_invitation', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_wiki', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'c_wiki_mailcue', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'extra_field_saved_search', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'gradebook_category', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'gradebook_certificate', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'gradebook_comment', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'gradebook_linkeval_log', 'field' => 'user_id_log', 'action' => 'delete'],
            ['table' => 'gradebook_result', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'gradebook_result_log', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'gradebook_score_log', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'message', 'field' => 'user_sender_id', 'action' => 'update'],
            ['table' => 'message_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'message_tag', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'notification', 'field' => 'dest_user_id', 'action' => 'delete'],
            ['table' => 'page_category', 'field' => 'creator_id', 'action' => 'update'],
            ['table' => 'portfolio_category', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'resource_comment', 'field' => 'author_id', 'action' => 'update'],
            ['table' => 'sequence_value', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'session_rel_course_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'session_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'skill_rel_item_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'skill_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'skill_rel_user_comment', 'field' => 'feedback_giver_id', 'action' => 'delete'],
            ['table' => 'social_post', 'field' => 'sender_id', 'action' => 'update'],
            ['table' => 'social_post', 'field' => 'user_receiver_id', 'action' => 'update'],
            ['table' => 'social_post_attachments', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'social_post_attachments', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'social_post_feedback', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'templates', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'ticket_assigned_log', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'ticket_assigned_log', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_category', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_category', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'ticket_category_rel_user', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'ticket_message', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_message', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'ticket_message_attachments', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_message_attachments', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'ticket_priority', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_priority', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'ticket_project', 'field' => 'sys_insert_user_id', 'action' => 'update'],
            ['table' => 'ticket_project', 'field' => 'sys_lastedit_user_id', 'action' => 'update'],
            ['table' => 'track_e_access', 'field' => 'access_user_id', 'action' => 'delete'],
            ['table' => 'track_e_access_complete', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'track_e_attempt', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'track_e_course_access', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'track_e_default', 'field' => 'default_user_id', 'action' => 'update'],
            ['table' => 'track_e_downloads', 'field' => 'down_user_id', 'action' => 'delete'],
            ['table' => 'track_e_exercises', 'field' => 'exe_user_id', 'action' => 'delete'],
            ['table' => 'track_e_exercise_confirmation', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'track_e_hotpotatoes', 'field' => 'exe_user_id', 'action' => 'delete'],
            ['table' => 'track_e_hotspot', 'field' => 'hotspot_user_id', 'action' => 'delete'],
            ['table' => 'track_e_lastaccess', 'field' => 'access_user_id', 'action' => 'delete'],
            ['table' => 'track_e_links', 'field' => 'links_user_id', 'action' => 'delete'],
            ['table' => 'track_e_login', 'field' => 'login_user_id', 'action' => 'delete'],
            ['table' => 'track_e_online', 'field' => 'login_user_id', 'action' => 'delete'],
            ['table' => 'track_e_uploads', 'field' => 'upload_user_id', 'action' => 'delete'],
            ['table' => 'usergroup_rel_user', 'field' => 'user_id', 'action' => 'update'],
            ['table' => 'user_rel_tag', 'field' => 'user_id', 'action' => 'delete'],
            ['table' => 'user_rel_user', 'field' => 'user_id', 'action' => 'delete'],
        ];
    }

    /**
     * Deletes a user's messages and their attachments, updates the message content,
     * and detaches the user as the sender.
     */
    public function deleteUserMessagesAndAttachments(User $user): void
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $currentDate = (new Datetime())->format('Y-m-d H:i:s');
        $updatedContent = \sprintf(
            $this->translator->trans('This message was deleted when the user was removed from the platform on %s'),
            $currentDate
        );

        $connection->executeStatement(
            'UPDATE message m
         SET m.content = :content, m.user_sender_id = NULL
         WHERE m.user_sender_id = :userId',
            [
                'content' => $updatedContent,
                'userId' => $user->getId(),
            ]
        );

        $connection->executeStatement(
            'DELETE ma
         FROM message_attachment ma
         INNER JOIN message m ON ma.message_id = m.id
         WHERE m.user_sender_id IS NULL',
            [
                'userId' => $user->getId(),
            ]
        );

        $em->clear();
    }

    public function getFallbackUser(): ?User
    {
        return $this->findOneBy(['status' => User::ROLE_FALLBACK], ['id' => 'ASC']);
    }

    public function addUserToResourceNode(int $userId, int $creatorId): ResourceNode
    {
        /** @var User $user */
        $user = $this->find($userId);
        $creator = $this->find($creatorId);

        $resourceNode = (new ResourceNode())
            ->setTitle($user->getUsername())
            ->setCreator($creator)
            ->setResourceType($this->getResourceType())
            // ->setParent($resourceNode)
        ;

        $user->setResourceNode($resourceNode);

        $this->getEntityManager()->persist($resourceNode);
        $this->getEntityManager()->persist($user);

        return $resourceNode;
    }

    public function addRoleListQueryBuilder(array $roles, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        if (!empty($roles)) {
            $orX = $qb->expr()->orX();
            foreach ($roles as $role) {
                $orX->add($qb->expr()->like('u.roles', ':'.$role));
                $qb->setParameter($role, '%'.$role.'%');
            }
            $qb->andWhere($orX);
        }

        return $qb;
    }

    public function findByUsername(string $username): ?User
    {
        $user = $this->findOneBy([
            'username' => $username,
        ]);

        if (null === $user) {
            throw new UserNotFoundException(\sprintf("User with id '%s' not found.", $username));
        }

        return $user;
    }

    public function findAllUsers(bool $useCache = false): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($useCache) {
            return $this->queryCacheHelper->run(
                $qb,
                'findAllUsers',
                []
            );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a filtered list of user by role and (optionally) access url.
     *
     * @param string $keyword     The query to filter
     * @param int    $accessUrlId The access URL ID
     *
     * @return User[]
     */
    public function findByRole(
        string $role,
        string $keyword,
        int $accessUrlId = 0,
        bool $useCache = false
    ) {
        $qb = $this->createQueryBuilder('u');

        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);
        $this->addRoleQueryBuilder($role, $qb);
        $this->addSearchByKeywordQueryBuilder($keyword, $qb);

        if ($useCache) {
            return $this->queryCacheHelper->run(
                $qb,
                'findByRole',
                [
                    'role' => $role,
                    'keyword' => $keyword,
                    'accessUrlId' => $accessUrlId,
                ]
            );
        }

        return $qb->getQuery()->getResult();
    }

    public function findByRoleList(
        array $roleList,
        string $keyword,
        int $accessUrlId = 0,
        bool $useCache = false
    ) {
        $qb = $this->createQueryBuilder('u');

        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);
        $this->addRoleListQueryBuilder($roleList, $qb);
        $this->addSearchByKeywordQueryBuilder($keyword, $qb);

        if ($useCache) {
            return $this->queryCacheHelper->run(
                $qb,
                'findByRoleList',
                [
                    'roles' => $roleList,
                    'keyword' => $keyword,
                    'accessUrlId' => $accessUrlId,
                ]
            );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the coaches for a course within a session.
     *
     * @return array<int, User>
     */
    public function getCoachesForSessionCourse(Session $session, Course $course): array
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u')
            ->innerJoin(
                SessionRelCourseRelUser::class,
                'scu',
                Join::WITH,
                'scu.user = u'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('scu.session', $session->getId()),
                    $qb->expr()->eq('scu.course', $course->getId()),
                    $qb->expr()->eq('scu.status', Session::COURSE_COACH)
                )
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the sessions admins for a user.
     *
     * @return array
     */
    public function getSessionAdmins(User $user)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->distinct()
            ->innerJoin(
                SessionRelUser::class,
                'su',
                Join::WITH,
                'u = su.user'
            )
            ->innerJoin(
                SessionRelCourseRelUser::class,
                'scu',
                Join::WITH,
                'su.session = scu.session'
            )
            ->where(
                $qb->expr()->eq('scu.user', $user->getId())
            )
            ->andWhere(
                $qb->expr()->eq('su.relationType', Session::DRH)
            )
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get number of users in URL.
     *
     * @return int
     */
    public function getCountUsersByUrl(AccessUrl $url)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->innerJoin('u.portals', 'p')
            ->where('p.url = :url')
            ->setParameters([
                'url' => $url,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Get number of users in URL.
     *
     * @return int
     */
    public function getCountTeachersByUrl(AccessUrl $url)
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->select('COUNT(u)')
            ->innerJoin('u.portals', 'p')
            ->where('p.url = :url')
            ->setParameters([
                'url' => $url,
            ])
        ;

        $this->addRoleListQueryBuilder(['ROLE_TEACHER'], $qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find potential users to send a message.
     *
     * @todo remove  api_is_platform_admin
     *
     * @param int    $currentUserId The current user ID
     * @param string $searchFilter  Optional. The search text to filter the user list
     * @param int    $limit         Optional. Sets the maximum number of results to retrieve
     *
     * @return User[]
     */
    public function findUsersToSendMessage(int $currentUserId, ?string $searchFilter = null, int $limit = 10)
    {
        $allowSendMessageToAllUsers = api_get_setting('allow_send_message_to_all_platform_users');
        $accessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id() : 1;

        $messageTool = 'true' === api_get_setting('allow_message_tool');
        if (!$messageTool) {
            return [];
        }

        $qb = $this->createQueryBuilder('u');
        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);

        $dql = null;
        if ('true' === api_get_setting('allow_social_tool')) {
            // All users
            if ('true' === $allowSendMessageToAllUsers || api_is_platform_admin()) {
                $this->addNotCurrentUserQueryBuilder($currentUserId, $qb);
            /*$dql = "SELECT DISTINCT U
                    FROM ChamiloCoreBundle:User U
                    LEFT JOIN ChamiloCoreBundle:AccessUrlRelUser R
                    WITH U = R.user
                    WHERE
                        U.active = 1 AND
                        U.status != 6  AND
                        U.id != {$currentUserId} AND
                        R.url = {$accessUrlId}";*/
            } else {
                $this->addOnlyMyFriendsQueryBuilder($currentUserId, $qb);
                /*$dql = 'SELECT DISTINCT U
                        FROM ChamiloCoreBundle:AccessUrlRelUser R, ChamiloCoreBundle:UserRelUser UF
                        INNER JOIN ChamiloCoreBundle:User AS U
                        WITH UF.friendUserId = U
                        WHERE
                            U.active = 1 AND
                            U.status != 6 AND
                            UF.relationType NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.") AND
                            UF.user = {$currentUserId} AND
                            UF.friendUserId != {$currentUserId} AND
                            U = R.user AND
                            R.url = {$accessUrlId}";*/
            }
        } else {
            if ('true' === $allowSendMessageToAllUsers) {
                $this->addNotCurrentUserQueryBuilder($currentUserId, $qb);
            } else {
                return [];
            }

            /*else {
                $time_limit = (int) api_get_setting('time_limit_whosonline');
                $online_time = time() - ($time_limit * 60);
                $limit_date = api_get_utc_datetime($online_time);
                $dql = "SELECT DISTINCT U
                        FROM ChamiloCoreBundle:User U
                        INNER JOIN ChamiloCoreBundle:TrackEOnline T
                        WITH U.id = T.loginUserId
                        WHERE
                          U.active = 1 AND
                          T.loginDate >= '".$limit_date."'";
            }*/
        }

        if (!empty($searchFilter)) {
            $this->addSearchByKeywordQueryBuilder($searchFilter, $qb);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the list of HRM who have assigned this user.
     *
     * @return User[]
     */
    public function getAssignedHrmUserList(int $userId, int $urlId)
    {
        $qb = $this->createQueryBuilder('u');
        $this->addAccessUrlQueryBuilder($urlId, $qb);
        $this->addActiveAndNotAnonUserQueryBuilder($qb);
        $this->addUserRelUserQueryBuilder($userId, UserRelUser::USER_RELATION_TYPE_RRHH, $qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the last login from the track_e_login table.
     * This might be different from user.last_login in the case of legacy users
     * as user.last_login was only implemented in 1.10 version with a default
     * value of NULL (not the last record from track_e_login).
     *
     * @return null|TrackELogin
     */
    public function getLastLogin(User $user)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->select('l')
            ->innerJoin('u.logins', 'l')
            ->where(
                $qb->expr()->eq('l.user', $user)
            )
            ->setMaxResults(1)
            ->orderBy('u.loginDate', Criteria::DESC)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function addAccessUrlQueryBuilder(int $accessUrlId, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');

        if ($accessUrlId > 0) {
            $qb
                ->innerJoin('u.portals', 'p')
                ->andWhere('p.url = :url')
                ->setParameter('url', $accessUrlId, Types::INTEGER)
            ;
        }

        return $qb;
    }

    public function addActiveAndNotAnonUserQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.active = 1')
            ->andWhere('u.status <> :status')
            ->setParameter('status', User::ANONYMOUS, Types::INTEGER)
        ;

        return $qb;
    }

    public function addExpirationDateQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.expirationDate IS NULL OR u.expirationDate > :now')
            ->setParameter('now', new Datetime(), Types::DATETIME_MUTABLE)
        ;

        return $qb;
    }

    private function addRoleQueryBuilder(string $role, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%', Types::STRING)
        ;

        return $qb;
    }

    private function addSearchByKeywordQueryBuilder(string $keyword, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('
                u.firstname LIKE :keyword OR
                u.lastname LIKE :keyword OR
                u.email LIKE :keyword OR
                u.username LIKE :keyword
            ')
            ->setParameter('keyword', "%$keyword%", Types::STRING)
            ->orderBy('u.firstname', Criteria::ASC)
        ;

        return $qb;
    }

    private function addUserRelUserQueryBuilder(int $userId, int $relationType, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb->leftJoin('u.friends', 'relations');
        $qb
            ->andWhere('relations.relationType = :relationType')
            ->andWhere('relations.user = :userRelation AND relations.friend <> :userRelation')
            ->setParameter('relationType', $relationType)
            ->setParameter('userRelation', $userId)
        ;

        return $qb;
    }

    private function addOnlyMyFriendsQueryBuilder(int $userId, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->leftJoin('u.friends', 'relations')
            ->andWhere(
                $qb->expr()->notIn(
                    'relations.relationType',
                    [UserRelUser::USER_RELATION_TYPE_DELETED, UserRelUser::USER_RELATION_TYPE_RRHH]
                )
            )
            ->andWhere('relations.user = :user AND relations.friend <> :user')
            ->setParameter('user', $userId, Types::INTEGER)
        ;

        return $qb;
    }

    private function addNotCurrentUserQueryBuilder(int $userId, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'u');
        $qb
            ->andWhere('u.id <> :id')
            ->setParameter('id', $userId, Types::INTEGER)
        ;

        return $qb;
    }

    public function getFriendsNotInGroup(int $userId, int $groupId)
    {
        $entityManager = $this->getEntityManager();

        $subQueryBuilder = $entityManager->createQueryBuilder();
        $subQuery = $subQueryBuilder
            ->select('IDENTITY(ugr.user)')
            ->from(UsergroupRelUser::class, 'ugr')
            ->where('ugr.usergroup = :subGroupId')
            ->andWhere('ugr.relationType IN (:subRelationTypes)')
            ->getDQL()
        ;

        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('u')
            ->from(User::class, 'u')
            ->leftJoin('u.friendsWithMe', 'uruf')
            ->leftJoin('u.friends', 'urut')
            ->where('uruf.friend = :userId OR urut.user = :userId')
            ->andWhere($queryBuilder->expr()->notIn('u.id', $subQuery))
            ->setParameter('userId', $userId)
            ->setParameter('subGroupId', $groupId)
            ->setParameter('subRelationTypes', [Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION])
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function getExtraUserData(int $userId, bool $prefix = false, bool $allVisibility = true, bool $splitMultiple = false, ?int $fieldFilter = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // Start building the query
        $qb->select('ef.id', 'ef.variable as fvar', 'ef.valueType as type', 'efv.fieldValue as fval', 'ef.defaultValue as fval_df')
            ->from(ExtraField::class, 'ef')
            ->leftJoin(ExtraFieldValues::class, 'efv', Join::WITH, 'efv.field = ef.id AND efv.itemId = :userId')
            ->where('ef.itemType = :itemType')
            ->setParameter('userId', $userId)
            ->setParameter('itemType', ExtraField::USER_FIELD_TYPE)
        ;

        // Apply visibility filters
        if (!$allVisibility) {
            $qb->andWhere('ef.visibleToSelf = true');
        }

        // Apply field filter if provided
        if (null !== $fieldFilter) {
            $qb->andWhere('ef.id = :fieldFilter')
                ->setParameter('fieldFilter', $fieldFilter)
            ;
        }

        // Order by field order
        $qb->orderBy('ef.fieldOrder', 'ASC');

        // Execute the query
        $results = $qb->getQuery()->getResult();

        // Process results
        $extraData = [];
        foreach ($results as $row) {
            $value = $row['fval'] ?? $row['fval_df'];

            // Handle multiple values if necessary
            if ($splitMultiple && \in_array($row['type'], [ExtraField::USER_FIELD_TYPE_SELECT_MULTIPLE], true)) {
                $value = explode(';', $value);
            }

            // Handle prefix if needed
            $key = $prefix ? 'extra_'.$row['fvar'] : $row['fvar'];

            // Special handling for certain field types
            if (ExtraField::USER_FIELD_TYPE_TAG == $row['type']) {
                // Implement your logic to handle tags
            } elseif (ExtraField::USER_FIELD_TYPE_RADIO == $row['type'] && $prefix) {
                $extraData[$key][$key] = $value;
            } else {
                $extraData[$key] = $value;
            }
        }

        return $extraData;
    }

    public function getExtraUserDataByField(int $userId, string $fieldVariable, bool $allVisibility = true): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e.id, e.variable, e.valueType, v.fieldValue')
            ->from(ExtraFieldValues::class, 'v')
            ->innerJoin('v.field', 'e')
            ->where('v.itemId = :userId')
            ->andWhere('e.variable = :fieldVariable')
            ->andWhere('e.itemType = :itemType')
            ->setParameters([
                'userId' => $userId,
                'fieldVariable' => $fieldVariable,
                'itemType' => ExtraField::USER_FIELD_TYPE,
            ])
        ;

        if (!$allVisibility) {
            $qb->andWhere('e.visibleToSelf = true');
        }

        $qb->orderBy('e.fieldOrder', 'ASC');

        $result = $qb->getQuery()->getResult();

        $extraData = [];
        foreach ($result as $row) {
            $value = $row['fieldValue'];
            if (ExtraField::USER_FIELD_TYPE_SELECT_MULTIPLE == $row['valueType']) {
                $value = explode(';', $row['fieldValue']);
            }

            $extraData[$row['variable']] = $value;
        }

        return $extraData;
    }

    public function searchUsersByTags(
        string $tag,
        ?int $excludeUserId = null,
        int $fieldId = 0,
        int $from = 0,
        int $number_of_items = 10,
        bool $getCount = false
    ): array {
        $qb = $this->createQueryBuilder('u');

        if ($getCount) {
            $qb->select('COUNT(DISTINCT u.id)');
        } else {
            $qb->select('DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, u.pictureUri, u.status');
        }

        $qb->innerJoin('u.portals', 'urlRelUser')
            ->leftJoin(UserRelTag::class, 'uv', 'WITH', 'u = uv.user')
            ->leftJoin(Tag::class, 'ut', 'WITH', 'uv.tag = ut')
        ;

        if (0 !== $fieldId) {
            $qb->andWhere('ut.field = :fieldId')
                ->setParameter('fieldId', $fieldId)
            ;
        }

        if (null !== $excludeUserId) {
            $qb->andWhere('u.id != :excludeUserId')
                ->setParameter('excludeUserId', $excludeUserId)
            ;
        }

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('ut.tag', ':tag'),
                $qb->expr()->like('u.firstname', ':likeTag'),
                $qb->expr()->like('u.lastname', ':likeTag'),
                $qb->expr()->like('u.username', ':likeTag'),
                $qb->expr()->like(
                    $qb->expr()->concat('u.firstname', $qb->expr()->literal(' '), 'u.lastname'),
                    ':likeTag'
                ),
                $qb->expr()->like(
                    $qb->expr()->concat('u.lastname', $qb->expr()->literal(' '), 'u.firstname'),
                    ':likeTag'
                )
            )
        )
            ->setParameter('tag', $tag.'%')
            ->setParameter('likeTag', '%'.$tag.'%')
        ;

        // Only active users and not anonymous
        $qb->andWhere('u.active = :active')
            ->andWhere('u.status != :anonymous')
            ->setParameter('active', true)
            ->setParameter('anonymous', 6)
        ;

        if (!$getCount) {
            $qb->orderBy('u.username')
                ->setFirstResult($from)
                ->setMaxResults($number_of_items)
            ;
        }

        return $getCount ? $qb->getQuery()->getSingleScalarResult() : $qb->getQuery()->getResult();
    }

    public function getUserRelationWithType(int $userId, int $friendId): ?array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.id AS userId', 'u.username AS userName', 'ur.relationType', 'f.id AS friendId', 'f.username AS friendName')
            ->innerJoin('u.friends', 'ur')
            ->innerJoin('ur.friend', 'f')
            ->where('u.id = :userId AND f.id = :friendId')
            ->setParameter('userId', $userId)
            ->setParameter('friendId', $friendId)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function relateUsers(User $user1, User $user2, int $relationType): void
    {
        $em = $this->getEntityManager();

        $existingRelation = $em->getRepository(UserRelUser::class)->findOneBy([
            'user' => $user1,
            'friend' => $user2,
        ]);

        if (!$existingRelation) {
            $newRelation = new UserRelUser();
            $newRelation->setUser($user1);
            $newRelation->setFriend($user2);
            $newRelation->setRelationType($relationType);
            $em->persist($newRelation);
        } else {
            $existingRelation->setRelationType($relationType);
        }

        $existingRelationInverse = $em->getRepository(UserRelUser::class)->findOneBy([
            'user' => $user2,
            'friend' => $user1,
        ]);

        if (!$existingRelationInverse) {
            $newRelationInverse = new UserRelUser();
            $newRelationInverse->setUser($user2);
            $newRelationInverse->setFriend($user1);
            $newRelationInverse->setRelationType($relationType);
            $em->persist($newRelationInverse);
        } else {
            $existingRelationInverse->setRelationType($relationType);
        }

        $em->flush();
    }

    public function getUserPicture(
        $userId,
        int $size = self::USER_IMAGE_SIZE_MEDIUM,
        $addRandomId = true,
    ) {
        $user = $this->find($userId);
        if (!$user) {
            return '/img/icons/64/unknown.png';
        }

        switch ($size) {
            case self::USER_IMAGE_SIZE_SMALL:
                $width = 32;

                break;

            case self::USER_IMAGE_SIZE_MEDIUM:
                $width = 64;

                break;

            case self::USER_IMAGE_SIZE_BIG:
                $width = 128;

                break;

            case self::USER_IMAGE_SIZE_ORIGINAL:
            default:
                $width = 0;

                break;
        }

        $url = $this->illustrationRepository->getIllustrationUrl($user);
        $params = [];
        if (!empty($width)) {
            $params['w'] = $width;
        }

        if ($addRandomId) {
            $params['rand'] = uniqid('u_', true);
        }

        $paramsToString = '';
        if (!empty($params)) {
            $paramsToString = '?'.http_build_query($params);
        }

        return $url.$paramsToString;
    }

    /**
     * Retrieves the list of DRH (HR) users related to a specific user and access URL.
     */
    public function getDrhListFromUser(int $userId, int $accessUrlId): array
    {
        $qb = $this->createQueryBuilder('u');

        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);

        $qb->select('u.id, u.username, u.firstname, u.lastname')
            ->innerJoin('u.friends', 'uru', Join::WITH, 'uru.friend = u.id')
            ->where('uru.user = :userId')
            ->andWhere('uru.relationType = :relationType')
            ->setParameter('userId', $userId)
            ->setParameter('relationType', UserRelUser::USER_RELATION_TYPE_RRHH)
        ;

        $qb->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findUsersByContext(int $courseId, ?int $sessionId = null, ?int $groupId = null): array
    {
        $course = $this->_em->getRepository(Course::class)->find($courseId);
        if (!$course) {
            throw new InvalidArgumentException('Course not found.');
        }

        if (null !== $sessionId) {
            $session = $this->_em->getRepository(Session::class)->find($sessionId);
            if (!$session) {
                throw new InvalidArgumentException('Session not found.');
            }

            $list = $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT);
            $users = [];

            if ($list) {
                foreach ($list as $sessionCourseUser) {
                    $users[$sessionCourseUser->getUser()->getId()] = $sessionCourseUser->getUser();
                }
            }

            return array_values($users);
        }

        if (null !== $groupId) {
            $qb = $this->_em->createQueryBuilder();
            $qb->select('u')
                ->from(CGroupRelUser::class, 'cgru')
                ->innerJoin('cgru.user', 'u')
                ->where('cgru.cId = :courseId')
                ->andWhere('cgru.group = :groupId')
                ->setParameters([
                    'courseId' => $courseId,
                    'groupId' => $groupId,
                ])
                ->orderBy('u.lastname', 'ASC')
                ->addOrderBy('u.firstname', 'ASC')
            ;

            return $qb->getQuery()->getResult();
        }

        $queryBuilder = $this->_em->getRepository(Course::class)->getSubscribedStudents($course);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findUsersForSessionAdmin(
        ?string $lastname,
        ?string $firstname,
        ?array $extraFilters,
        ?AccessUrl $accessUrl
    ): array {
        $qb = $this->createQueryBuilder('u');

        if (null !== $accessUrl) {
            $qb->join('u.portals', 'p')
                ->andWhere('p.url = :url')
                ->setParameter('url', $accessUrl)
            ;
        }

        $qb->andWhere('u.active != :softDeleted')
            ->setParameter('softDeleted', -2)
        ;

        if (!empty($lastname)) {
            $qb->andWhere('u.lastname LIKE :lastname')
                ->setParameter('lastname', '%'.$lastname.'%')
            ;
        }

        if (!empty($firstname)) {
            $qb->andWhere('u.firstname LIKE :firstname')
                ->setParameter('firstname', '%'.$firstname.'%')
            ;
        }

        if (!empty($extraFilters)) {
            foreach ($extraFilters as $field => $value) {
                $qb->andWhere(\sprintf(
                    'EXISTS (
                    SELECT 1
                    FROM Chamilo\CoreBundle\Entity\ExtraFieldValues efv
                    JOIN efv.field ef
                    WHERE efv.itemId = u.id
                      AND ef.variable = :field_%s
                      AND efv.fieldValue LIKE :value_%s
                )',
                    $field,
                    $field
                ));
                $qb->setParameter('field_'.$field, $field);
                $qb->setParameter('value_'.$field, '%'.$value.'%');
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the number of users registered with a given email.
     */
    public function countUsersByEmail(string $email): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array<int, User>
     */
    public function findByAuthsource(string $authentication): array
    {
        $qb = $this->getOrCreateQueryBuilder(null, 'u');

        return $qb
            ->innerJoin('u.authSources', 'as')
            ->where(
                $qb->expr()->eq('as.authentication', ':authentication')
            )
            ->setParameter('authentication', $authentication)
            ->getQuery()
            ->getResult()
        ;
    }

    public function deactivateUsers(array $ids): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->update(User::class, 'u')
            ->set('u.active', ':active')
            ->where(
                $qb->expr()->in('u.id', ':ids')
            )
            ->setParameters([
                'active' => false,
                'ids' => $ids,
            ])
        ;
    }

    public function findOnePlatformAdmin(?int $accessUrlId = null): ?User
    {
        $qb = $this->createQueryBuilder('u');
        $this->addActiveAndNotAnonUserQueryBuilder($qb);

        if (null === $accessUrlId) {
            $accessUrlId = api_get_multiple_access_url() ? api_get_current_access_url_id() : 0;
        }
        $this->addAccessUrlQueryBuilder($accessUrlId, $qb);

        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('u.roles', ':super'),
            $qb->expr()->like('u.roles', ':admin')
        ))
            ->setParameter('super', '%ROLE_SUPER_ADMIN%')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the "package" {id, username, email} to autocomplete the export.
     * Try: Admin::class -> roles -> current user -> fallback "1/admin/admin@example.com".
     */
    public function getDefaultAdminForExport(): array
    {
        $em = $this->getEntityManager();

        try {
            $adminEntity = $em->getRepository(Admin::class)
                ->createQueryBuilder('a')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if ($adminEntity && $adminEntity->getUser()) {
                $u = $adminEntity->getUser();

                return [
                    'id' => (string) $u->getId(),
                    'username' => (string) $u->getUsername(),
                    'email' => (string) ($u->getEmail() ?? ''),
                ];
            }
        } catch (Throwable $e) {
        }

        $u = $this->findOnePlatformAdmin();
        if ($u instanceof User) {
            return [
                'id' => (string) $u->getId(),
                'username' => (string) $u->getUsername(),
                'email' => (string) ($u->getEmail() ?? ''),
            ];
        }

        $me = api_get_user_info();
        if (!empty($me['id'])) {
            return [
                'id' => (string) $me['id'],
                'username' => (string) ($me['username'] ?? ''),
                'email' => (string) ($me['email'] ?? ''),
            ];
        }

        return [
            'id' => '1',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ];
    }
}
