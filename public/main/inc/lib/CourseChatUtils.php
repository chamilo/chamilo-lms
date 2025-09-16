<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CChatConnected;
use Chamilo\CourseBundle\Entity\CChatConversation;
use Doctrine\Common\Collections\Criteria;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Course chat utils.
 */
class CourseChatUtils
{
    private $groupId;
    private $courseId;
    private $sessionId;
    private $userId;

    /** @var ResourceNode */
    private $resourceNode;

    /** @var ResourceRepository */
    private $repository;

    /** Debug flag */
    private $debug = true;

    public function __construct($courseId, $userId, $sessionId, $groupId, ResourceNode $resourceNode, ResourceRepository $repository)
    {
        $this->courseId     = (int) $courseId;
        $this->userId       = (int) $userId;
        $this->sessionId    = (int) $sessionId;
        $this->groupId      = (int) $groupId;
        $this->resourceNode = $resourceNode;
        $this->repository   = $repository;

        $this->dbg('construct', [
            'courseId'     => $courseId,
            'userId'       => $userId,
            'sessionId'    => $sessionId,
            'groupId'      => $groupId,
            'parentNodeId' => $resourceNode->getId() ?? null,
            'repo'         => get_class($repository),
        ]);
    }

    /** Simple debug helper */
    private function dbg(string $msg, array $ctx = []): void
    {
        if (!$this->debug) { return; }
        $line = '[CourseChat] '.$msg;
        if ($ctx) { $line .= ' | '.json_encode($ctx, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }
        error_log($line);
    }

    /** Build a slug out of the file title (matches our nodes like: messages-...-log-html) */
    private function makeSlug(string $fileTitle): string
    {
        $slug = strtolower($fileTitle);
        $slug = strtr($slug, ['.' => '-']);
        $slug = preg_replace('~[^a-z0-9\-\_]+~', '-', $slug);
        $slug = preg_replace('~-+~', '-', $slug);
        return trim($slug, '-');
    }

    /** Build a base name by day and scope (all / session / group / 1:1) */
    private function buildBasename(int $friendId = 0): string
    {
        $dateNow  = date('Y-m-d');
        $basename = 'messages-'.$dateNow;

        if ($this->groupId && !$friendId) {
            $basename .= '_gid-'.$this->groupId;
        } elseif ($this->sessionId && !$friendId) {
            $basename .= '_sid-'.$this->sessionId;
        } elseif ($friendId) {
            // stable order for 1:1 (smallest id first)
            $basename .= ($this->userId < $friendId)
                ? '_uid-'.$this->userId.'-'.$friendId
                : '_uid-'.$friendId.'-'.$this->userId;
        }
        return $basename;
    }

    /** Returns [fileTitle, slug] */
    private function buildNames(int $friendId = 0): array
    {
        $fileTitle = $this->buildBasename($friendId).'-log.html';
        $slug      = $this->makeSlug($fileTitle);
        return [$fileTitle, $slug];
    }

    /** Create node + conversation + empty file (used only from saveMessage under a lock) */
    private function createNodeWithResource(string $fileTitle, string $slug, ResourceNode $parentNode): ResourceNode
    {
        $em = Database::getManager();

        $this->dbg('node.create.start', ['slug' => $slug, 'title' => $fileTitle, 'parent' => $parentNode->getId()]);

        // temporary empty file
        $h = tmpfile();
        fwrite($h, '');
        $meta     = stream_get_meta_data($h);
        $uploaded = new UploadedFile($meta['uri'], $fileTitle, 'text/html', null, true);

        // conversation
        $conversation = new CChatConversation();
        if (method_exists($conversation, 'setTitle')) {
            $conversation->setTitle($fileTitle);
        } else {
            $conversation->setResourceName($fileTitle);
        }
        $conversation->setParentResourceNode($parentNode->getId());

        // node
        $node = new ResourceNode();
        $node->setTitle($fileTitle);
        $node->setSlug($slug);
        $node->setResourceType($parentNode->getResourceType());
        $node->setCreator(api_get_user_entity(api_get_user_id()));
        $node->setParent($parentNode);

        if (method_exists($conversation, 'setResourceNode')) {
            $conversation->setResourceNode($node);
        }

        $em->persist($node);
        $em->persist($conversation);

        // attach file
        $this->repository->addFile($conversation, $uploaded);

        // publish
        $course  = api_get_course_entity();
        $session = api_get_session_entity();
        $group   = api_get_group_entity();
        $conversation->setParent($course);
        $conversation->addCourseLink($course, $session, $group);

        $em->flush();

        $this->dbg('node.create.ok', ['nodeId' => $node->getId()]);

        return $node;
    }

    /** Sanitize and convert message to safe HTML */
    public function prepareMessage($message)
    {
        $this->dbg('prepareMessage.in', ['len' => strlen((string) $message)]);
        if (empty($message)) {
            return '';
        }

        $message = trim($message);
        $message = nl2br($message);
        $message = Security::remove_XSS($message);

        // url -> anchor
        $message = preg_replace(
            '@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $message
        );
        // add http:// when missing
        $message = preg_replace(
            '/<a\s[^>]*href\s*=\s*"((?!https?:\/\/)[^"]*)"[^>]*>/i',
            '<a href="http://$1" target="_blank" rel="noopener noreferrer">',
            $message
        );

        $message = MarkdownExtra::defaultTransform($message);
        $this->dbg('prepareMessage.out', ['len' => strlen($message)]);

        return $message;
    }

    /**
     * Return the latest node (by id DESC) matching title or slug under the same parent.
     * Read-only; does not create.
     */
    private function findExistingNode(string $fileTitle, string $slug, ResourceNode $parentNode): ?ResourceNode
    {
        $em = \Database::getManager();
        $nodeRepo = $em->getRepository(ResourceNode::class);

        // latest by exact title
        $node = $nodeRepo->findOneBy(
            ['title' => $fileTitle, 'parent' => $parentNode],
            ['id' => 'DESC']
        );
        if ($node) { return $node; }

        // latest by exact slug
        return $nodeRepo->findOneBy(
            ['slug' => $slug, 'parent' => $parentNode],
            ['id' => 'DESC']
        );
    }

    /**
     * Append a message to the last daily file (chronological order).
     */
    public function saveMessage($message, $friendId = 0)
    {
        $this->dbg('saveMessage.in', ['friendId' => (int)$friendId, 'rawLen' => strlen((string)$message)]);
        if (!is_string($message) || trim($message) === '') { return false; }

        // names (one file per day/scope)
        [$fileTitle, $slug] = $this->buildNames((int)$friendId);

        $em = Database::getManager();
        /** @var ResourceNodeRepository $nodeRepo */
        $nodeRepo = $em->getRepository(ResourceNode::class);
        $convRepo = $em->getRepository(CChatConversation::class);
        $rfRepo   = $em->getRepository(ResourceFile::class);

        // parent (chat root)
        $parent = $nodeRepo->find($this->resourceNode->getId());
        if (!$parent) { $this->dbg('saveMessage.error.noParent'); return false; }

        // serialize writers for the same daily file (parent+slug)
        $lockPath = sys_get_temp_dir().'/ch_chat_lock_'.$parent->getId().'_'.$slug.'.lock';
        $lockH = @fopen($lockPath, 'c');
        if ($lockH) { @flock($lockH, LOCK_EX); }

        try {
            // latest node for this day/scope (title OR slug)
            $qb = $em->createQueryBuilder();
            $qb->select('n')
                ->from(ResourceNode::class, 'n')
                ->where('n.parent = :parent AND (n.title = :title OR n.slug = :slug)')
                ->setParameter('parent', $parent)
                ->setParameter('title', $fileTitle)
                ->setParameter('slug',  $slug)
                ->orderBy('n.createdAt', 'DESC')
                ->addOrderBy('n.id', 'DESC')
                ->setMaxResults(1);
            /** @var ResourceNode|null $node */
            $node = $qb->getQuery()->getOneOrNullResult();

            // create node + conversation once
            if (!$node) {
                $conversation = new CChatConversation();
                (method_exists($conversation, 'setTitle')
                    ? $conversation->setTitle($fileTitle)
                    : $conversation->setResourceName($fileTitle));
                $conversation->setParentResourceNode($parent->getId());

                $node = new ResourceNode();
                $node->setTitle($fileTitle);
                $node->setSlug($slug);
                $node->setResourceType($parent->getResourceType());
                $node->setCreator(api_get_user_entity(api_get_user_id()));
                $node->setParent($parent);

                if (method_exists($conversation, 'setResourceNode')) {
                    $conversation->setResourceNode($node);
                }

                $em->persist($node);
                $em->persist($conversation);

                $course  = api_get_course_entity();
                $session = api_get_session_entity();
                $group   = api_get_group_entity();
                $conversation->setParent($course);
                $conversation->addCourseLink(
                    $course, $session, $group
                );

                $em->flush();
            }

            // ensure conversation exists for node
            $conversation = $convRepo->findOneBy(['resourceNode' => $node]);
            if (!$conversation) {
                $conversation = new CChatConversation();
                (method_exists($conversation, 'setTitle')
                    ? $conversation->setTitle($fileTitle)
                    : $conversation->setResourceName($fileTitle));
                $conversation->setParentResourceNode($parent->getId());
                if (method_exists($conversation, 'setResourceNode')) {
                    $conversation->setResourceNode($node);
                }
                $em->persist($conversation);

                $course  = api_get_course_entity();
                $session = api_get_session_entity();
                $group   = api_get_group_entity();
                $conversation->setParent($course);
                $conversation->addCourseLink(
                    $course, $session, $group
                );

                $em->flush();
            }

            // build message bubble
            $user      = api_get_user_entity($this->userId);
            $isMaster  = api_is_course_admin();
            $timeNow   = date('d/m/y H:i:s');
            $userPhoto = \UserManager::getUserPicture($this->userId);
            $htmlMsg   = $this->prepareMessage($message);

            $bubble = $isMaster
                ? '<div class="message-teacher"><div class="content-message"><div class="chat-message-block-name">'
                .\UserManager::formatUserFullName($user).'</div><div class="chat-message-block-content">'
                .$htmlMsg.'</div><div class="message-date">'.$timeNow
                .'</div></div><div class="icon-message"></div><img class="chat-image" src="'.$userPhoto.'"></div>'
                : '<div class="message-student"><img class="chat-image" src="'.$userPhoto.'"><div class="icon-message"></div>'
                .'<div class="content-message"><div class="chat-message-block-name">'.\UserManager::formatUserFullName($user)
                .'</div><div class="chat-message-block-content">'.$htmlMsg.'</div><div class="message-date">'
                .$timeNow.'</div></div></div>';

            // always target latest ResourceFile for today (by id desc)
            $rfQb = $em->createQueryBuilder();
            $rf   = $rfQb->select('rf')
                ->from(ResourceFile::class, 'rf')
                ->where('rf.resourceNode = :node AND rf.originalName = :name')
                ->setParameter('node', $node)
                ->setParameter('name', $fileTitle)
                ->orderBy('rf.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // read current content and append
            $existing = '';
            if ($rf) {
                try { $existing = $nodeRepo->getResourceNodeFileContent($node, $rf) ?? ''; }
                catch (\Throwable $e) { $existing = ''; }
            }
            $newContent = $existing.$bubble;

            // write back (reuse same physical path)
            if ($rf) {
                $fs       = $nodeRepo->getFileSystem();
                $fileName = $nodeRepo->getFilename($rf);
                if ($fs->fileExists($fileName)) { $fs->delete($fileName); }
                $fs->write($fileName, $newContent);
                if (method_exists($rf, 'setSize')) { $rf->setSize(strlen($newContent)); $em->persist($rf); }
                $em->flush();
            } else {
                // first write of the day → create the ResourceFile with the whole content
                if (method_exists($this->repository, 'addFileFromString')) {
                    $this->repository->addFileFromString($conversation, $fileTitle, 'text/html', $newContent, true);
                    $em->flush();
                } else {
                    $h = tmpfile(); fwrite($h, $newContent);
                    $meta = stream_get_meta_data($h);
                    $uploaded = new UploadedFile(
                        $meta['uri'], $fileTitle, 'text/html', null, true
                    );
                    $this->repository->addFile($conversation, $uploaded);
                    $em->flush();
                }
            }

            $this->dbg('saveMessage.append.ok', ['nodeId' => $node->getId(), 'bytes' => strlen($newContent)]);
            return true;

        } catch (\Throwable $e) {
            $this->dbg('saveMessage.error', ['err' => $e->getMessage()]);
            return false;
        } finally {
            if ($lockH) { @flock($lockH, LOCK_UN); @fclose($lockH); }
        }
    }

    /**
     * Read the last daily file HTML (optionally reset it).
     */
    public function readMessages($reset = false, $friendId = 0)
    {
        [$fileTitle, $slug] = $this->buildNames((int)$friendId);

        $this->dbg('readMessages.in', [
            'friendId' => (int)$friendId,
            'reset'    => (bool)$reset,
            'file'     => $fileTitle,
            'slug'     => $slug,
        ]);

        $em = \Database::getManager();
        /** @var ResourceNodeRepository $nodeRepo */
        $nodeRepo = $em->getRepository(ResourceNode::class);

        $parent = $nodeRepo->find($this->resourceNode->getId());
        if (!$parent) { $this->dbg('readMessages.error.noParent'); return ''; }

        // read-only: do not create
        $node = $this->findExistingNode($fileTitle, $slug, $parent);
        if (!$node) { $this->dbg('readMessages.notfound'); return ''; }

        // locate the same ResourceFile by originalName (latest id desc)
        $rfRepo = $em->getRepository(ResourceFile::class);
        /** @var ResourceFile|null $rf */
        $rf = $rfRepo->findOneBy(
            ['resourceNode' => $node, 'originalName' => $fileTitle],
            ['id' => 'DESC']
        );

        // optional reset
        if ($reset) {
            $target = $rf ?: ($node->getResourceFiles()->first() ?: null);
            if ($target) {
                $fs       = $nodeRepo->getFileSystem();
                $fileName = $nodeRepo->getFilename($target);
                if ($fs->fileExists($fileName)) {
                    $fs->delete($fileName);
                    $fs->write($fileName, '');
                }
                if (method_exists($target, 'setSize')) { $target->setSize(0); $em->persist($target); }
                $em->flush();
                $this->dbg('readMessages.reset.ok', ['nodeId' => $node->getId(), 'rfId' => $target->getId()]);
            }
        }

        try {
            // primary: exact RF by originalName
            if ($rf) {
                $html = $nodeRepo->getResourceNodeFileContent($node, $rf);
                $this->dbg('readMessages.out.byOriginalName', [
                    'nodeId' => $node->getId(),
                    'rfId'   => $rf->getId(),
                    'bytes'  => strlen($html ?? ''),
                ]);
                return $html ?? '';
            }

            // fallback: first attached file (covers legacy hashed names)
            $html = $nodeRepo->getResourceNodeFileContent($node);
            $this->dbg('readMessages.out.fallbackFirst', [
                'nodeId' => $node->getId(),
                'bytes'  => strlen($html ?? ''),
            ]);
            return $html ?? '';

        } catch (\Throwable $e) {
            $this->dbg('readMessages.read.error', ['err' => $e->getMessage()]);
            return '';
        }
    }

    /** Force a user to exit all course chat connections */
    public static function exitChat($userId)
    {
        $listCourse = CourseManager::get_courses_list_by_user_id($userId);
        foreach ($listCourse as $course) {
            Database::getManager()
                ->createQuery('
                    DELETE FROM ChamiloCourseBundle:CChatConnected ccc
                    WHERE ccc.cId = :course AND ccc.userId = :user
                ')
                ->execute([
                    'course' => intval($course['real_id']),
                    'user'   => intval($userId),
                ]);
        }
    }

    /** Remove inactive connections (simple heartbeat) */
    public function disconnectInactiveUsers(): void
    {
        $em = Database::getManager();
        $extraCondition = $this->groupId
            ? "AND ccc.toGroupId = {$this->groupId}"
            : "AND ccc.sessionId = {$this->sessionId}";

        $connectedUsers = $em
            ->createQuery("
                SELECT ccc FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.cId = :course $extraCondition
            ")
            ->setParameter('course', $this->courseId)
            ->getResult();

        $now  = new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC'));
        $nowTs = $now->getTimestamp();

        /** @var CChatConnected $connection */
        foreach ($connectedUsers as $connection) {
            $lastTs = $connection->getLastConnection()->getTimestamp();
            if (0 !== strcmp($now->format('Y-m-d'), $connection->getLastConnection()->format('Y-m-d'))) {
                continue;
            }
            if (($nowTs - $lastTs) <= 5) {
                continue;
            }

            $em
                ->createQuery('
                    DELETE FROM ChamiloCourseBundle:CChatConnected ccc
                    WHERE ccc.cId = :course AND ccc.userId = :user AND ccc.toGroupId = :group
                ')
                ->execute([
                    'course' => $this->courseId,
                    'user'   => $connection->getUserId(),
                    'group'  => $this->groupId,
                ]);
        }
    }

    /** Keep (or create) the "connected" record for current user */
    public function keepUserAsConnected(): void
    {
        $em = Database::getManager();
        $extraCondition = $this->groupId
            ? 'AND ccc.toGroupId = '.$this->groupId
            : 'AND ccc.sessionId = '.$this->sessionId;

        $currentTime = new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC'));

        /** @var CChatConnected|null $connection */
        $connection = $em
            ->createQuery("
                SELECT ccc FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.userId = :user AND ccc.cId = :course $extraCondition
            ")
            ->setParameters([
                'user'   => $this->userId,
                'course' => $this->courseId,
            ])
            ->getOneOrNullResult();

        if ($connection) {
            $connection->setLastConnection($currentTime);
            $em->persist($connection);
            $em->flush();
            return;
        }

        $connection = new CChatConnected();
        $connection
            ->setCId($this->courseId)
            ->setUserId($this->userId)
            ->setLastConnection($currentTime)
            ->setSessionId($this->sessionId)
            ->setToGroupId($this->groupId);

        $em->persist($connection);
        $em->flush();
    }

    /** Legacy helper (kept for BC) */
    public function getFileName($absolute = false, $friendId = 0): string
    {
        $base = $this->buildBasename((int)$friendId).'.log.html';
        if (!$absolute) { return $base; }

        $document_path = '/document';
        $chatPath = $document_path.'/chat_files/';

        if ($this->groupId) {
            $group_info = GroupManager::get_group_properties($this->groupId);
            $chatPath = $document_path.$group_info['directory'].'/chat_files/';
        }

        return $chatPath.$base;
    }

    /** Count users online (simple 5s heartbeat window) */
    public function countUsersOnline(): int
    {
        $date = new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC'));
        $date->modify('-5 seconds');

        $extraCondition = $this->groupId
            ? 'AND ccc.toGroupId = '.$this->groupId
            : 'AND ccc.sessionId = '.$this->sessionId;

        $number = Database::getManager()
            ->createQuery("
                SELECT COUNT(ccc.userId) FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.lastConnection > :date AND ccc.cId = :course $extraCondition
            ")
            ->setParameters([
                'date'   => $date,
                'course' => $this->courseId,
            ])
            ->getSingleScalarResult();

        return (int) $number;
    }

    /** Return basic info for connected/eligible users */
    public function listUsersOnline(): array
    {
        $subscriptions = $this->getUsersSubscriptions();
        $usersInfo = [];

        if ($this->groupId) {
            /** @var User $groupUser */
            foreach ($subscriptions as $groupUser) {
                $usersInfo[] = $this->formatUser($groupUser, $groupUser->getStatus());
            }
        } else {
            /** @var CourseRelUser|SessionRelCourseRelUser $subscription */
            foreach ($subscriptions as $subscription) {
                $user = $subscription->getUser();
                $usersInfo[] = $this->formatUser(
                    $user,
                    $this->sessionId ? $user->getStatus() : $subscription->getStatus()
                );
            }
        }

        return $usersInfo;
    }

    /** Normalize user card info */
    private function formatUser(User $user, $status): array
    {
        return [
            'id'            => $user->getId(),
            'firstname'     => $user->getFirstname(),
            'lastname'      => $user->getLastname(),
            'status'        => $status,
            'image_url'     => UserManager::getUserPicture($user->getId()),
            'profile_url'   => api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user->getId(),
            'complete_name' => UserManager::formatUserFullName($user),
            'username'      => $user->getUsername(),
            'email'         => $user->getEmail(),
            'isConnected'   => $this->userIsConnected($user->getId()),
        ];
    }

    /** Fetch subscriptions (course / session / group) */
    private function getUsersSubscriptions()
    {
        $em = Database::getManager();

        if ($this->groupId) {
            $students = $em
                ->createQuery(
                    'SELECT u FROM ChamiloCoreBundle:User u
                     INNER JOIN ChamiloCourseBundle:CGroupRelUser gru
                        WITH u.id = gru.userId AND gru.cId = :course
                     WHERE u.id != :user AND gru.groupId = :group
                       AND u.active = true'
                )
                ->setParameters(['course' => $this->courseId, 'user' => $this->userId, 'group' => $this->groupId])
                ->getResult();

            $tutors = $em
                ->createQuery(
                    'SELECT u FROM ChamiloCoreBundle:User u
                     INNER JOIN ChamiloCourseBundle:CGroupRelTutor grt
                        WITH u.id = grt.userId AND grt.cId = :course
                     WHERE u.id != :user AND grt.groupId = :group
                       AND u.active = true'
                )
                ->setParameters(['course' => $this->courseId, 'user' => $this->userId, 'group' => $this->groupId])
                ->getResult();

            return array_merge($tutors, $students);
        }

        $course = api_get_course_entity($this->courseId);

        if ($this->sessionId) {
            $session   = api_get_session_entity($this->sessionId);
            $criteria  = Criteria::create()->where(Criteria::expr()->eq('course', $course));
            $userCoach = api_is_course_session_coach($this->userId, $course->getId(), $session->getId());

            if ('true' === api_get_setting('chat.course_chat_restrict_to_coach')) {
                if ($userCoach) {
                    $criteria->andWhere(Criteria::expr()->eq('status', Session::STUDENT));
                } else {
                    $criteria->andWhere(Criteria::expr()->eq('status', Session::COURSE_COACH));
                }
            }

            $criteria->orderBy(['status' => Criteria::DESC]);

            return $session
                ->getUserCourseSubscriptions()
                ->matching($criteria)
                ->filter(function (SessionRelCourseRelUser $scru) {
                    return $scru->getUser()->isActive();
                });
        }

        return $course
            ->getUsers()
            ->filter(function (CourseRelUser $cru) {
                return $cru->getUser()->isActive();
            });
    }

    /** Quick online check for one user */
    private function userIsConnected($userId): int
    {
        $date = new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC'));
        $date->modify('-5 seconds');

        $extraCondition = $this->groupId
            ? 'AND ccc.toGroupId = '.$this->groupId
            : 'AND ccc.sessionId = '.$this->sessionId;

        $number = Database::getManager()
            ->createQuery("
                SELECT COUNT(ccc.userId) FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.lastConnection > :date AND ccc.cId = :course AND ccc.userId = :user $extraCondition
            ")
            ->setParameters([
                'date'   => $date,
                'course' => $this->courseId,
                'user'   => $userId,
            ])
            ->getSingleScalarResult();

        return (int) $number;
    }
}
