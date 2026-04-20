<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\PageHelper;
use Chamilo\CoreBundle\Helpers\ResourceAclHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelDocument;
use ChamiloSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<'CREATE'|'VIEW'|'EDIT'|'DELETE'|'EXPORT', ResourceNode>
 */
class ResourceNodeVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const EXPORT = 'EXPORT';
    public const ROLE_CURRENT_COURSE_TEACHER = 'ROLE_CURRENT_COURSE_TEACHER';
    public const ROLE_CURRENT_COURSE_STUDENT = 'ROLE_CURRENT_COURSE_STUDENT';
    public const ROLE_CURRENT_COURSE_GROUP_TEACHER = 'ROLE_CURRENT_COURSE_GROUP_TEACHER';
    public const ROLE_CURRENT_COURSE_GROUP_STUDENT = 'ROLE_CURRENT_COURSE_GROUP_STUDENT';
    public const ROLE_CURRENT_COURSE_SESSION_TEACHER = 'ROLE_CURRENT_COURSE_SESSION_TEACHER';
    public const ROLE_CURRENT_COURSE_SESSION_STUDENT = 'ROLE_CURRENT_COURSE_SESSION_STUDENT';

    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private SettingsManager $settingsManager,
        private EntityManagerInterface $entityManager,
        private PageHelper $pageHelper,
        private readonly ResourceAclHelper $resourceAclHelper,
    ) {}

    public static function getReaderMask(): int
    {
        return ResourceAclHelper::getPermissionMask([self::VIEW]);
    }

    public static function getEditorMask(): int
    {
        return ResourceAclHelper::getPermissionMask([self::VIEW, self::EDIT]);
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::EXPORT,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // only vote on ResourceNode objects inside this voter
        return $subject instanceof ResourceNode;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var ResourceNode $resourceNode */
        $resourceNode = $subject;
        $resourceTypeName = $resourceNode->getResourceType()->getTitle();

        // Illustrations are always visible, nothing to check.
        if ('illustrations' === $resourceTypeName) {
            return true;
        }

        // Courses are also a Resource but courses are protected using the CourseVoter, not by ResourceNodeVoter.
        if ('courses' === $resourceTypeName) {
            return true;
        }

        // Checking admin role.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (self::VIEW === $attribute && $this->canViewOwnStudentPublicationRelatedResource($resourceNode, $token)) {
            return true;
        }

        if (self::VIEW === $attribute && $this->isBlogResource($resourceNode)) {
            return true;
        }

        // Special case: allow file assets that are embedded inside a visible system announcement.
        if (self::VIEW === $attribute && $this->isAnnouncementFileVisibleForCurrentRequest($resourceNode, $token)) {
            return true;
        }

        // Special case: allow quiz attempt feedback audio files to be played by
        // authorized users (student/teacher) when opened from exercise/LP views.
        if (self::VIEW === $attribute && $this->isQuizAttemptFeedbackVisibleForCurrentRequest($resourceNode, $token)) {
            return true;
        }

        if (self::VIEW === $attribute && $this->isPersonalDocumentFileVisibleForCurrentRequest($resourceNode, $token)) {
            return true;
        }

        if (self::VIEW === $attribute && $this->isPersonalFileVisibleForAll($resourceNode, $token)) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                if ($resourceNode->isPublic()) {
                    return true;
                }

                // Exception: allow access to hotspot question images if student can view the quiz
                $questionRepo = $this->entityManager->getRepository(CQuizQuestion::class);
                $question = $questionRepo->findOneBy(['resourceNode' => $resourceNode]);
                if ($question) {
                    // Check if it's a Hotspot-type question
                    if (\in_array($question->getType(), [6, 7, 8, 20, 13], true)) { // HOT_SPOT, HOT_SPOT_ORDER, HOT_SPOT_DELINEATION, ANNOTATION
                        $rel = $this->entityManager
                            ->getRepository(CQuizRelQuestion::class)
                            ->findOneBy(['question' => $question])
                        ;

                        if ($rel && $rel->getQuiz()) {
                            $quiz = $rel->getQuiz();
                            // Allow if the user has VIEW rights on the quiz
                            if ($this->security->isGranted('VIEW', $quiz)) {
                                return true;
                            }
                        }
                    }
                }

                // no break
            case self::EDIT:
                break;
        }

        $user = $token->getUser();
        // Check if I'm the owner.
        $creator = $resourceNode->getCreator();

        if ($creator instanceof UserInterface
            && $user instanceof UserInterface
            && $user->getUserIdentifier() === $creator->getUserIdentifier()
        ) {
            return true;
        }

        $resourceTypeTitle = $resourceNode->getResourceType()->getTitle();
        if (
            \in_array($resourceTypeTitle, [
                'student_publications',
                'student_publications_corrections',
                'student_publications_comments',
            ], true)
        ) {
            if ($creator instanceof UserInterface
                && $user instanceof UserInterface
                && $user->getUserIdentifier() === $creator->getUserIdentifier()
            ) {
                return true;
            }

            if ($this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
                || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
                || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            ) {
                return true;
            }
        }

        if ('files' === $resourceNode->getResourceType()->getTitle()) {
            $document = $this->entityManager
                ->getRepository(CDocument::class)
                ->findOneBy(['resourceNode' => $resourceNode])
            ;

            if ($document) {
                $exists = $this->entityManager
                    ->getRepository(CStudentPublicationRelDocument::class)
                    ->findOneBy(['document' => $document])
                ;

                if (null !== $exists) {
                    return true;
                }
            }
        }

        // Checking links connected to this resource.
        $request = $this->requestStack->getCurrentRequest();

        $courseId = 0;
        $sessionId = 0;
        $groupId = 0;
        $isFromLearningPath = false;

        if (null !== $request) {
            $courseId = (int) $request->get('cid');
            $sessionId = (int) $request->get('sid');
            $groupId = (int) $request->get('gid');

            // Detect learning path context from request parameters.
            $lpId = $request->query->getInt('lp_id', 0);
            $lpItemId = $request->query->getInt('lp_item_id', 0);
            $origin = (string) $request->query->get('origin', '');

            $isFromLearningPath = $lpId > 0 || $lpItemId > 0 || 'learnpath' === $origin;

            // Try Session values.
            if (empty($courseId) && $request->hasSession()) {
                $courseId = (int) $request->getSession()->get('cid');
                $sessionId = (int) $request->getSession()->get('sid');
                $groupId = (int) $request->getSession()->get('gid');

                if (0 === $courseId) {
                    $courseId = (int) ChamiloSession::read('cid');
                    $sessionId = (int) ChamiloSession::read('sid');
                    $groupId = (int) ChamiloSession::read('gid');
                }
            }
        }

        $links = $resourceNode->getResourceLinks();
        $firstLink = $links->first();
        if ($resourceNode->hasResourceFile() && $firstLink) {
            if (0 === $courseId && $firstLink->getCourse() instanceof Course) {
                $courseId = (int) $firstLink->getCourse()->getId();
            }
            if (0 === $sessionId && $firstLink->getSession() instanceof Session) {
                $sessionId = (int) $firstLink->getSession()->getId();
            }
            if (0 === $groupId && $firstLink->getGroup() instanceof CGroup) {
                $groupId = (int) $firstLink->getGroup()->getIid();
            }
            if ($firstLink->getCourse() instanceof Course
                && $firstLink->getCourse()->isPublic()
            ) {
                return true;
            }
        }

        $linkFound = 0;
        $link = null;

        foreach ($links as $link) {
            // Check if resource was sent to the current user.
            $linkUser = $link->getUser();
            if ($linkUser instanceof UserInterface
                && $user instanceof UserInterface
                && $linkUser->getUserIdentifier() === $user->getUserIdentifier()) {
                $linkFound = 2;

                break;
            }

            $linkCourse = $link->getCourse();

            // Course found, but courseId not set, skip course checking.
            if ($linkCourse instanceof Course && empty($courseId)) {
                continue;
            }

            $linkSession = $link->getSession();
            $linkGroup = $link->getGroup();

            if (null === $linkUser
                && $linkGroup instanceof CGroup && !empty($groupId)
                && $linkSession instanceof Session && !empty($sessionId)
                && $linkCourse instanceof Course
                && ($linkCourse->getId() === $courseId
                && $linkSession->getId() === $sessionId
                && $linkGroup->getIid() === $groupId)
            ) {
                $linkFound = 3;

                break;
            }

            // Check if resource was sent inside a group in a base course.
            if (null === $linkUser
                && empty($sessionId)
                && $linkGroup instanceof CGroup && !empty($groupId)
                && $linkCourse instanceof Course
                && ($linkCourse->getId() === $courseId && $linkGroup->getIid() === $groupId)
                // Prevent matching a session-scoped group link as a base-course group link.
                && null === $linkSession
            ) {
                $linkFound = 4;

                break;
            }

            // Check if resource was sent to a course inside a session.
            if (null === $linkUser
                && $linkSession instanceof Session && !empty($sessionId)
                && $linkCourse instanceof Course
                && ($linkCourse->getId() === $courseId && $linkSession->getId() === $sessionId)
                // Prevent leaking group-scoped resources into the session course context.
                && null === $linkGroup
            ) {
                $linkFound = 5;

                break;
            }

            // Check if resource was sent to a course (course context only).
            if (null === $linkUser
                && $linkCourse instanceof Course
                && $linkCourse->getId() === $courseId
                && null === $linkSession
                && null === $linkGroup
            ) {
                $linkFound = 6;

                break;
            }
        }

        if (0 === $linkFound) {
            return false;
        }

        // Getting rights from the link
        $rightsFromResourceLink = $link->getResourceRights();

        $rights = [];
        if ($rightsFromResourceLink->count() > 0) {
            // Taken rights from the link.
            $rights = $rightsFromResourceLink;
        }

        // By default, the rights are:
        // Teachers: CRUD.
        // Students: Only read.
        // Anons: Only read.
        $readerMask = self::getReaderMask();
        $editorMask = self::getEditorMask();

        if ($courseId && $link->hasCourse() && $link->getCourse()->getId() === $courseId) {
            // If teacher.
            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_TEACHER)) {
                $resourceRight = (new ResourceRight())
                    ->setMask($editorMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_TEACHER)
                ;
                $rights[] = $resourceRight;
            }

            // If student.
            // Normal case: resource must be published.
            // Exception: when the resource is being opened from a learning path item,
            // allow VIEW even if the underlying ResourceLink visibility is hidden in the tool.
            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_STUDENT)
                && (ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility() || $isFromLearningPath)
            ) {
                $resourceRight = (new ResourceRight())
                    ->setMask($readerMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_STUDENT)
                ;
                $rights[] = $resourceRight;
            }

            // For everyone.
            if (ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()
                && $link->getCourse()->isPublic()
            ) {
                $resourceRight = (new ResourceRight())
                    ->setMask($readerMask)
                    ->setRole('IS_AUTHENTICATED_ANONYMOUSLY')
                ;
                $rights[] = $resourceRight;
            }
        }

        if (!empty($groupId)) {
            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_GROUP_TEACHER)) {
                $resourceRight = (new ResourceRight())
                    ->setMask($editorMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_GROUP_TEACHER)
                ;
                $rights[] = $resourceRight;
            }

            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_GROUP_STUDENT)) {
                $resourceRight = (new ResourceRight())
                    ->setMask($readerMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_GROUP_STUDENT)
                ;
                $rights[] = $resourceRight;
            }
        }

        if (!empty($sessionId)) {
            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_SESSION_TEACHER)) {
                $resourceRight = (new ResourceRight())
                    ->setMask($editorMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_SESSION_TEACHER)
                ;
                $rights[] = $resourceRight;
            }

            if ($this->security->isGranted(self::ROLE_CURRENT_COURSE_SESSION_STUDENT)) {
                $resourceRight = (new ResourceRight())
                    ->setMask($readerMask)
                    ->setRole(self::ROLE_CURRENT_COURSE_SESSION_STUDENT)
                ;
                $rights[] = $resourceRight;
            }
        }

        if (empty($rights) && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
            // Give just read access.
            $resourceRight = (new ResourceRight())
                ->setMask($readerMask)
                ->setRole('ROLE_USER')
            ;
            $rights[] = $resourceRight;
        }

        return $this->resourceAclHelper->isAllowed($attribute, $link, $rights);
    }

    /**
     * Checks if the current request is viewing a document file that is embedded
     * inside a visible system announcement, delegating the heavy logic to PageHelper.
     */
    private function isAnnouncementFileVisibleForCurrentRequest(ResourceNode $resourceNode, TokenInterface $token): bool
    {
        $type = $resourceNode->getResourceType()?->getTitle();
        if ('files' !== $type) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $pathInfo = (string) $request->getPathInfo();
        if ('' === $pathInfo) {
            return false;
        }

        // Extract file identifier from /r/document/files/{identifier}/view.
        $segments = explode('/', trim($pathInfo, '/'));
        $identifier = null;
        if (\count($segments) >= 4) {
            // ... /r/document/files/{identifier}/view
            $identifier = $segments[\count($segments) - 2] ?? null;
        }

        $userFromToken = $token->getUser();
        $user = $userFromToken instanceof UserInterface ? $userFromToken : null;
        $locale = $request->getLocale();

        return $this->pageHelper->isFilePathExposedByVisibleAnnouncement(
            $pathInfo,
            \is_string($identifier) ? $identifier : null,
            $user,
            $locale
        );
    }

    /**
     * Allows access to quiz attempt feedback audio files for authenticated users.
     *
     * This is a narrow compatibility exception for:
     * /r/quiz/attempt_feedback/{uuid}/view
     *
     * It avoids depending on cid/sid contextual roles, which are often missing
     * on direct /r/... resource requests.
     */
    private function isQuizAttemptFeedbackVisibleForCurrentRequest(ResourceNode $resourceNode, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $pathInfo = (string) $request->getPathInfo();
        if ('' === $pathInfo) {
            return false;
        }

        // Match only:
        // /r/quiz/attempt_feedback/{uuid}/view
        // /r/quiz/attempt_file/{uuid}/view
        if (!preg_match('#^/r/quiz/(attempt_feedback|attempt_file)/[0-9a-fA-F-]{36}/view$#', $pathInfo)) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Reject explicit cross-site requests when headers are present.
        if ($this->hasCrossSiteHeaderMismatch($request)) {
            return false;
        }

        // Keep the exception scoped to expected resource types.
        $type = strtolower((string) ($resourceNode->getResourceType()?->getTitle() ?? ''));
        if (!\in_array($type, ['files', 'quiz', 'attempt_feedback', 'attempt_file'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Allows access to personal files for all authenticated users when the
     * corresponding platform setting is enabled.
     */
    private function isPersonalFileVisibleForAll(ResourceNode $resourceNode, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$resourceNode->hasResourceFile()) {
            return false;
        }

        if (!$this->isTruthySettingValue(
            $this->settingsManager->getSetting('security.access_to_personal_file_for_all', true)
        )) {
            return false;
        }

        return $this->isPersonalFileResource($resourceNode);
    }

    /**
     * Allows authenticated users to view direct document file resources that
     * are backed by a personal file when the global setting is enabled.
     *
     * This targets URLs like:
     * /r/document/files/{uuid}/view
     */
    private function isPersonalDocumentFileVisibleForCurrentRequest(ResourceNode $resourceNode, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $pathInfo = (string) $request->getPathInfo();
        if ('' === $pathInfo) {
            return false;
        }

        if (!preg_match('#^/r/document/files/[0-9a-fA-F-]{36}/view$#', $pathInfo)) {
            return false;
        }

        if ($this->hasCrossSiteHeaderMismatch($request)) {
            return false;
        }

        if (!$resourceNode->hasResourceFile()) {
            return false;
        }

        if (!$this->isTruthySettingValue(
            $this->settingsManager->getSetting('security.access_to_personal_file_for_all', true)
        )) {
            return false;
        }

        return $this->isPersonalFileResource($resourceNode);
    }

    /**
     * Detects whether the resource node belongs to the personal files area.
     *
     * The primary source of truth is the personal_file table because some
     * personal files do not have any resource_link row.
     */
    private function isPersonalFileResource(ResourceNode $resourceNode): bool
    {
        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if ($resourceLink->getUser() instanceof UserInterface) {
                return true;
            }
        }

        $resourceNodeId = $resourceNode->getId();
        if (null === $resourceNodeId) {
            return false;
        }

        $sql = 'SELECT 1 FROM personal_file WHERE resource_node_id = :resourceNodeId LIMIT 1';
        $exists = $this->entityManager
            ->getConnection()
            ->fetchOne($sql, ['resourceNodeId' => $resourceNodeId])
        ;

        return false !== $exists && null !== $exists;
    }

    private function isTruthySettingValue(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return \in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Returns true when Origin/Referer explicitly points to another host.
     * Missing headers are allowed (common in some browsers/privacy settings).
     */
    private function hasCrossSiteHeaderMismatch(Request $request): bool
    {
        $currentHost = (string) $request->getSchemeAndHttpHost();

        foreach (['origin', 'referer'] as $headerName) {
            $headerValue = trim((string) $request->headers->get($headerName, ''));
            if ('' === $headerValue) {
                continue;
            }

            $parsed = parse_url($headerValue);
            if (!\is_array($parsed)) {
                continue;
            }

            $scheme = isset($parsed['scheme']) ? (string) $parsed['scheme'] : '';
            $host = isset($parsed['host']) ? (string) $parsed['host'] : '';
            $port = isset($parsed['port']) ? (int) $parsed['port'] : null;

            if ('' === $scheme || '' === $host) {
                continue;
            }

            $headerHost = $scheme.'://'.$host.(null !== $port ? ':'.$port : '');
            if ($headerHost !== $currentHost) {
                return true;
            }
        }

        return false;
    }

    private function isBlogResource(ResourceNode $node): bool
    {
        $type = $node->getResourceType()?->getTitle();
        if (\in_array($type, ['blog', 'blogs', 'c_blog', 'c_blogs'], true)) {
            return true;
        }

        $firstLink = $node->getResourceLinks()->first();
        if ($firstLink && method_exists($firstLink, 'getTool') && $firstLink->getTool()) {
            $toolName = method_exists($firstLink->getTool(), 'getName')
                ? $firstLink->getTool()->getName()
                : $firstLink->getTool()->getTitle();

            if (\in_array(strtolower((string) $toolName), ['blog', 'blogs'], true)) {
                return true;
            }
        }

        return false;
    }

    private function canViewOwnStudentPublicationRelatedResource(ResourceNode $resourceNode, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $resourceTypeTitle = $resourceNode->getResourceType()->getTitle();

        if ('student_publications' === $resourceTypeTitle) {
            $publication = $this->entityManager
                ->getRepository(CStudentPublication::class)
                ->findOneBy(['resourceNode' => $resourceNode])
            ;

            if ($publication instanceof CStudentPublication) {
                return $publication->getUser()->getUserIdentifier() === $user->getUserIdentifier();
            }

            return false;
        }

        if ('student_publications_comments' === $resourceTypeTitle) {
            $comment = $this->entityManager
                ->getRepository(CStudentPublicationComment::class)
                ->findOneBy(['resourceNode' => $resourceNode])
            ;

            if ($comment instanceof CStudentPublicationComment) {
                return $comment->getPublication()->getUser()->getUserIdentifier() === $user->getUserIdentifier();
            }

            return false;
        }

        if ('student_publications_corrections' === $resourceTypeTitle) {
            $parentNode = $resourceNode->getParent();
            if (!$parentNode instanceof ResourceNode) {
                return false;
            }

            $publication = $this->entityManager
                ->getRepository(CStudentPublication::class)
                ->findOneBy(['resourceNode' => $parentNode])
            ;

            if ($publication instanceof CStudentPublication) {
                return $publication->getUser()->getUserIdentifier() === $user->getUserIdentifier();
            }

            return false;
        }

        return false;
    }
}
