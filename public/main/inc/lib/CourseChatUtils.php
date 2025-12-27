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
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
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
    private $debug = false;

    private bool $restrictToCoachSetting = false;
    private bool $savePrivateConversationsInDocuments = false;

    public function __construct($courseId, $userId, $sessionId, $groupId, ResourceNode $resourceNode, ResourceRepository $repository)
    {
        $this->courseId     = (int) $courseId;
        $this->userId       = (int) $userId;
        $this->sessionId    = (int) $sessionId;
        $this->groupId      = (int) $groupId;
        $this->resourceNode = $resourceNode;
        $this->repository   = $repository;

        $this->restrictToCoachSetting = ('true' === api_get_setting('chat.course_chat_restrict_to_coach'));
        $this->savePrivateConversationsInDocuments = ('true' === api_get_setting('chat.save_private_conversations_in_documents'));

        $this->dbg('construct', [
            'courseId'     => $courseId,
            'userId'       => $userId,
            'sessionId'    => $sessionId,
            'groupId'      => $groupId,
            'parentNodeId' => $resourceNode->getId() ?? null,
            'repo'         => get_class($repository),
        ]);
    }

    private function shouldMirrorToDocuments(int $friendId): bool
    {
        // Private 1:1 conversations should NOT be mirrored by default.
        if ($friendId > 0) {
            return $this->savePrivateConversationsInDocuments;
        }

        // General / session / group chat: keep current behavior (mirrored).
        return true;
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
        $node->setResourceType($this->repository->getResourceType());
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
        $course  = api_get_course_entity($this->courseId);
        $session = api_get_session_entity($this->sessionId);
        $group   = api_get_group_entity();
        $conversation->setParent($course);
        $conversation->addCourseLink($course, $session, $group);

        $em->flush();

        $this->dbg('node.create.ok', ['nodeId' => $node->getId()]);

        return $node;
    }

    /** Sanitize and convert message to safe HTML */
    public static function prepareMessage($message): string
    {
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

        return $message;
    }

    private function mirrorDailyCopyToDocuments(string $fileTitle, string $html): void
    {
        try {
            $em = Database::getManager();
            /** @var CDocumentRepository $docRepo */
            $docRepo = $em->getRepository(CDocument::class);

            $course  = api_get_course_entity($this->courseId);
            $session = api_get_session_entity($this->sessionId) ?: null;

            $top = $docRepo->ensureChatSystemFolder($course, $session);

            /** @var ResourceNodeRepository $nodeRepo */
            $nodeRepo = $em->getRepository(ResourceNode::class);

            $em->beginTransaction();
            try {
                try {
                    $em->getConnection()->executeStatement(
                        'SELECT id FROM resource_node WHERE id = ? FOR UPDATE',
                        [$top->getId()]
                    );
                } catch (\Throwable $e) {
                    error_log('[CourseChat] mirror FOR UPDATE skipped: '.$e->getMessage());
                }

                $fileNode = $docRepo->findChildDocumentFileByTitle($top, $fileTitle);

                if ($fileNode) {
                    /** @var ResourceFile|null $rf */
                    $rf = $em->getRepository(ResourceFile::class)->findOneBy(
                        ['resourceNode' => $fileNode, 'originalName' => $fileTitle],
                        ['id' => 'DESC']
                    );
                    if (!$rf) {
                        $rf = $em->getRepository(ResourceFile::class)->findOneBy(
                            ['resourceNode' => $fileNode],
                            ['id' => 'DESC']
                        );
                    }

                    if ($rf) {
                        $fs = $nodeRepo->getFileSystem();
                        $fname = $nodeRepo->getFilename($rf);
                        if ($fs->fileExists($fname)) { $fs->delete($fname); }
                        $fs->write($fname, $html);
                        if (method_exists($rf, 'setSize')) { $rf->setSize(strlen($html)); $em->persist($rf); }
                        $em->flush();
                    } else {
                        $h = tmpfile(); fwrite($h, $html);
                        $meta = stream_get_meta_data($h);
                        $uploaded = new UploadedFile($meta['uri'], $fileTitle, 'text/html', null, true);

                        $docRepo->createFileInFolder(
                            $course, $top, $uploaded, 'Daily chat copy',
                            ResourceLink::VISIBILITY_PUBLISHED, $session
                        );
                        $em->flush();
                    }

                } else {
                    $h = tmpfile(); fwrite($h, $html);
                    $meta = stream_get_meta_data($h);
                    $uploaded = new UploadedFile($meta['uri'], $fileTitle, 'text/html', null, true);

                    $docRepo->createFileInFolder(
                        $course, $top, $uploaded, 'Daily chat copy',
                        ResourceLink::VISIBILITY_PUBLISHED, $session
                    );
                    $em->flush();
                }

                $em->commit();
            } catch (UniqueConstraintViolationException $e) {
                $em->rollback();
                $fileNode = $docRepo->findChildDocumentFileByTitle($top, $fileTitle);
                if ($fileNode) {
                    /** @var ResourceFile|null $rf */
                    $rf = $em->getRepository(ResourceFile::class)->findOneBy(
                        ['resourceNode' => $fileNode, 'originalName' => $fileTitle],
                        ['id' => 'DESC']
                    ) ?: $em->getRepository(ResourceFile::class)->findOneBy(
                        ['resourceNode' => $fileNode],
                        ['id' => 'DESC']
                    );

                    if ($rf) {
                        $fs = $nodeRepo->getFileSystem();
                        $fname = $nodeRepo->getFilename($rf);
                        if ($fs->fileExists($fname)) { $fs->delete($fname); }
                        $fs->write($fname, $html);
                        if (method_exists($rf, 'setSize')) { $rf->setSize(strlen($html)); $em->persist($rf); }
                        $em->flush();
                        return;
                    }
                }
                throw $e;
            } catch (\Throwable $e) {
                $em->rollback();
                throw $e;
            }

        } catch (\Throwable $e) {
            $this->dbg('mirrorDailyCopy.error', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Return the latest *chat* node for today (by createdAt DESC, id DESC).
     * It filters by the chat resourceType to avoid collisions with Document nodes.
     */
    private function findExistingNode(string $fileTitle, string $slug, ResourceNode $parentNode): ?ResourceNode
    {
        $em = \Database::getManager();
        $rt = $this->repository->getResourceType();

        $qb = $em->createQueryBuilder();
        $qb->select('n')
            ->from(ResourceNode::class, 'n')
            ->where('n.parent = :parent')
            ->andWhere('n.resourceType = :rt')
            ->andWhere('(n.title = :title OR n.slug = :slug)')
            ->setParameter('parent', $parentNode)
            ->setParameter('rt', $rt)
            ->setParameter('title', $fileTitle)
            ->setParameter('slug',  $slug)
            ->orderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->setMaxResults(1);

        /** @var ResourceNode|null $node */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Append a message to the last daily file (chronological order).
     */
    public function saveMessage($message, $friendId = 0)
    {
        $this->dbg('saveMessage.in', ['friendId' => (int)$friendId, 'rawLen' => strlen((string)$message)]);
        if (!is_string($message) || trim($message) === '') { return false; }

        [$fileTitle, $slug] = $this->buildNames((int)$friendId);

        $em = \Database::getManager();
        /** @var ResourceNodeRepository $nodeRepo */
        $nodeRepo = $em->getRepository(ResourceNode::class);

        // Parent = chat root node (CChatConversation) provided by controller
        $parent = $nodeRepo->find($this->resourceNode->getId());
        if (!$parent) { $this->dbg('saveMessage.error.noParent'); return false; }

        // Best-effort file lock by day
        $lockPath = sys_get_temp_dir().'/ch_chat_lock_'.$parent->getId().'_'.$slug.'.lock';
        $lockH = @fopen($lockPath, 'c'); if ($lockH) { @flock($lockH, LOCK_EX); }

        try {
            $em->beginTransaction();
            try {
                $em->lock($parent, LockMode::PESSIMISTIC_WRITE);

                $node = $this->findExistingNode($fileTitle, $slug, $parent);

                if (!$node) {
                    // Create conversation + node (same as before)
                    $conversation = new CChatConversation();
                    (method_exists($conversation, 'setTitle')
                        ? $conversation->setTitle($fileTitle)
                        : $conversation->setResourceName($fileTitle));
                    $conversation->setParentResourceNode($parent->getId());

                    $node = new ResourceNode();
                    $node->setTitle($fileTitle);
                    $node->setSlug($slug);
                    $node->setResourceType($this->repository->getResourceType());
                    $node->setCreator(api_get_user_entity(api_get_user_id()));
                    $node->setParent($parent);

                    if (method_exists($conversation, 'setResourceNode')) {
                        $conversation->setResourceNode($node);
                    }

                    $em->persist($node);
                    $em->persist($conversation);

                    $course  = api_get_course_entity($this->courseId);
                    $session = api_get_session_entity($this->sessionId);
                    $group   = api_get_group_entity();
                    $conversation->setParent($course);
                    $conversation->addCourseLink($course, $session, $group);

                    $em->flush();
                }

                $em->commit();
            } catch (UniqueConstraintViolationException $e) {
                $em->rollback();
                $node = $this->findExistingNode($fileTitle, $slug, $parent);
                if (!$node) { throw $e; }
            } catch (\Throwable $e) {
                $em->rollback();
                throw $e;
            }

            // Ensure conversation still exists (as you already did)
            $conversation = $em->getRepository(CChatConversation::class)
                ->findOneBy(['resourceNode' => $node]);
            if (!$conversation) {
                $em->beginTransaction();
                try {
                    $em->lock($parent, LockMode::PESSIMISTIC_WRITE);

                    $conversation = new CChatConversation();
                    (method_exists($conversation, 'setTitle')
                        ? $conversation->setTitle($fileTitle)
                        : $conversation->setResourceName($fileTitle));
                    $conversation->setParentResourceNode($parent->getId());
                    if (method_exists($conversation, 'setResourceNode')) {
                        $conversation->setResourceNode($node);
                    }
                    $em->persist($conversation);

                    $course  = api_get_course_entity($this->courseId);
                    $session = api_get_session_entity($this->sessionId);
                    $group   = api_get_group_entity();
                    $conversation->setParent($course);
                    $conversation->addCourseLink($course, $session, $group);

                    $em->flush();
                    $em->commit();
                } catch (UniqueConstraintViolationException $e) {
                    $em->rollback();
                    $conversation = $em->getRepository(CChatConversation::class)
                        ->findOneBy(['resourceNode' => $node]);
                    if (!$conversation) { throw $e; }
                } catch (\Throwable $e) {
                    $em->rollback();
                    throw $e;
                }
            }

            // Bubble HTML (unchanged)
            $user      = api_get_user_entity($this->userId);
            $isMaster  = api_is_course_admin();
            $timeNow   = date('d/m/y H:i:s');
            $userPhoto = \UserManager::getUserPicture($this->userId);
            $htmlMsg   = self::prepareMessage($message);

            $bubble = $isMaster
                ? '<div class="message-teacher"><div class="content-message"><div class="chat-message-block-name">'
                .\UserManager::formatUserFullName($user).'</div><div class="chat-message-block-content">'
                .$htmlMsg.'</div><div class="message-date">'.$timeNow
                .'</div></div><div class="icon-message"></div><img class="chat-image" src="'.$userPhoto.'"></div>'
                : '<div class="message-student"><img class="chat-image" src="'.$userPhoto.'"><div class="icon-message"></div>'
                .'<div class="content-message"><div class="chat-message-block-name">'.\UserManager::formatUserFullName($user)
                .'</div><div class="chat-message-block-content">'.$htmlMsg.'</div><div class="message-date">'
                .$timeNow.'</div></div></div>';

            // Locate ResourceFile (same logic as before)
            $rf = $em->createQueryBuilder()
                ->select('rf')
                ->from(ResourceFile::class, 'rf')
                ->where('rf.resourceNode = :node AND rf.originalName = :name')
                ->setParameter('node', $node)
                ->setParameter('name', $fileTitle)
                ->orderBy('rf.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$rf) {
                $rf = $em->createQueryBuilder()
                    ->select('rf')
                    ->from(ResourceFile::class, 'rf')
                    ->where('rf.resourceNode = :node')
                    ->setParameter('node', $node)
                    ->orderBy('rf.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }

            $existing = '';
            if ($rf) {
                try { $existing = $nodeRepo->getResourceNodeFileContent($node, $rf) ?? ''; }
                catch (\Throwable $e) { $existing = ''; }
            }
            $newContent = $existing.$bubble;

            if ($rf) {
                $fs = $nodeRepo->getFileSystem();
                $fname = $nodeRepo->getFilename($rf);
                if ($fs->fileExists($fname)) { $fs->delete($fname); }
                $fs->write($fname, $newContent);
                if (method_exists($rf, 'setSize')) { $rf->setSize(strlen($newContent)); $em->persist($rf); }
                $em->flush();
            } else {
                if (method_exists($this->repository, 'addFileFromString')) {
                    $this->repository->addFileFromString($conversation, $fileTitle, 'text/html', $newContent, true);
                } else {
                    $h = tmpfile(); fwrite($h, $newContent);
                    $meta = stream_get_meta_data($h);
                    $uploaded = new UploadedFile(
                        $meta['uri'], $fileTitle, 'text/html', null, true
                    );
                    $this->repository->addFile($conversation, $uploaded);
                }
                $em->flush();
            }

            // Mirror in Documents only when allowed by admin setting
            if ($this->shouldMirrorToDocuments((int) $friendId)) {
                $this->mirrorDailyCopyToDocuments($fileTitle, $newContent);
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

    private function getConnectedUserIdSet(): array
    {
        $date = new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC'));
        $date->modify('-5 seconds');

        $extraCondition = $this->groupId
            ? 'AND ccc.toGroupId = '.$this->groupId
            : 'AND ccc.sessionId = '.$this->sessionId;

        $rows = Database::getManager()
            ->createQuery("
            SELECT ccc.userId AS uid
            FROM ChamiloCourseBundle:CChatConnected ccc
            WHERE ccc.lastConnection > :date
              AND ccc.cId = :course
              $extraCondition
        ")
            ->setParameters([
                'date' => $date,
                'course' => $this->courseId,
            ])
            ->getArrayResult();

        $set = [];
        foreach ($rows as $r) {
            $id = (int) ($r['uid'] ?? 0);
            if ($id > 0) {
                $set[$id] = true;
            }
        }

        return $set;
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

            $em->createQuery('
                DELETE FROM ChamiloCourseBundle:CChatConnected ccc
                WHERE ccc.cId = :course
                  AND ccc.userId = :user
                  AND ccc.sessionId = :sid
                  AND ccc.toGroupId = :gid
            ')->execute([
                'course' => $this->courseId,
                'user'   => $connection->getUserId(),
                'sid'    => $this->sessionId,
                'gid'    => $this->groupId,
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

        $connectedSet = $this->getConnectedUserIdSet();
        if ($this->groupId) {
            /** @var User $groupUser */
            foreach ($subscriptions as $groupUser) {
                $usersInfo[] = $this->formatUser($groupUser, $groupUser->getStatus(), $connectedSet);
            }
        } else {
            /** @var CourseRelUser|SessionRelCourseRelUser $subscription */
            foreach ($subscriptions as $subscription) {
                $user = $subscription->getUser();
                $usersInfo[] = $this->formatUser(
                    $user,
                    $this->sessionId ? $user->getStatus() : $subscription->getStatus(),
                    $connectedSet
                );
            }
        }

        return $usersInfo;
    }

    /** Normalize user card info */
    private function formatUser(User $user, $status, array $connectedSet): array
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
            'isConnected'   => isset($connectedSet[$user->getId()]),
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
