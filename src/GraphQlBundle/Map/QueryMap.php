<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class QueryMap.
 *
 * @package Chamilo\GraphQlBundle\Map
 */
class QueryMap extends ResolverMap implements ContainerAwareInterface
{
    use GraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    $context->offsetSet('course', null);
                    $context->offsetSet('session', null);

                    $method = 'resolve'.ucfirst($info->fieldName);

                    return $this->$method($args, $context);
                },
            ],
            'User' => [
                self::RESOLVE_FIELD => function (
                    User $user,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('user', $user);
                    $resolver = $this->container->get('chamilo_graphql.resolver.user');

                    return $this->resolveField($info->fieldName, $user, $resolver, $args, $context);
                },
            ],
            'UserMessage' => [
                self::RESOLVE_FIELD => function (
                    Message $message,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('sender' === $info->fieldName) {
                        return $message->getUserSender();
                    }

                    if ('hasAttachments' === $info->fieldName) {
                        return $message->getAttachments()->count() > 0;
                    }

                    return $this->resolveField($info->fieldName, $message);
                },
            ],
            'Course' => [
                self::RESOLVE_FIELD => function (
                    Course $course,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('course', $course);
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $this->resolveField($info->fieldName, $course, $resolver, $args, $context);
                },
            ],
            'ToolDescription' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('descriptions' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_graphql.resolver.course');

                        return $resolver->getDescriptions($tool, $context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            //'CourseDescription' => [],
            'ToolAnnouncements' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    if ('announcements' === $info->fieldName) {
                        return $resolver->getAnnouncements($tool, $context);
                    }

                    if ('announcement' === $info->fieldName) {
                        return $resolver->getAnnouncement($args['id'], $context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseAnnouncement' => [
                'content' => function (\stdClass $announcement, Argument $args, \ArrayObject $context) {
                    /** @var User $reader */
                    $reader = $context->offsetGet('user');
                    /** @var Course $course */
                    $course = $context->offsetGet('course');
                    /** @var Session $session */
                    $session = $context->offsetGet('session');

                    return \AnnouncementManager::parseContent(
                        $reader->getId(),
                        $announcement->content,
                        $course->getCode(),
                        $session ? $session->getId() : 0
                    );
                },
            ],
            'ToolNotebook' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('notes' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_graphql.resolver.course');

                        return $resolver->getNotes($context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseNote' => [
                'id' => function (CNotebook $note) {
                    return $note->getIid();
                },
            ],
            'ToolForums' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    if ('categories' === $info->fieldName) {
                        return $resolver->getForumCategories($context);
                    }

                    if ('forum' === $info->fieldName) {
                        return $resolver->getForum($args['id'], $context);
                    }

                    if ('thread' === $info->fieldName) {
                        return $resolver->getThread($args['id'], $context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseForumCategory' => [
                'id' => function (CForumCategory $category) {
                    return $category->getIid();
                },
                'title' => function (CForumCategory $category) {
                    return $category->getCatTitle();
                },
                'comment' => function (CForumCategory $category) {
                    return $category->getCatComment();
                },
                'forums' => function (CForumCategory $category, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $resolver->getForums($category, $context);
                },
            ],
            'CourseForum' => [
                'id' => function (CForumForum $forum) {
                    return $forum->getIid();
                },
                'title' => function (CForumForum $forum) {
                    return $forum->getForumTitle();
                },
                'comment' => function (CForumForum $forum) {
                    return $forum->getForumComment();
                },
                'numberOfThreads' => function (CForumForum $forum) {
                    return (int) $forum->getForumThreads();
                },
                'numberOfPosts' => function (CForumForum $forum) {
                    return (int) $forum->getForumPosts();
                },
                'threads' => function (CForumForum $forum, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $resolver->getThreads($forum, $context);
                },
            ],
            'CourseForumThread' => [
                'id' => function (CForumThread $thread) {
                    return $thread->getIid();
                },
                'title' => function (CForumThread $thread) {
                    return $thread->getThreadTitle();
                },
                'userPoster' => function (CForumThread $thread) {
                    $userRepo = $this->em->getRepository('ChamiloUserBundle:User');
                    $user = $userRepo->find($thread->getThreadPosterId());

                    return $user;
                },
                'date' => function (CForumThread $thread) {
                    return $thread->getThreadDate();
                },
                'sticky' => function (CForumThread $thread) {
                    return $thread->getThreadSticky();
                },
                'numberOfViews' => function (CForumThread $thread) {
                    return $thread->getThreadViews();
                },
                'numberOfReplies' => function (CForumThread $thread) {
                    return $thread->getThreadReplies();
                },
                'closeDate' => function (CForumThread $thread) {
                    return $thread->getThreadCloseDate();
                },
                'posts' => function (CForumThread $thread, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $resolver->getPosts($thread, $context);
                },
            ],
            'CourseForumPost' => [
                'id' => function (CForumPost $post) {
                    return $post->getIid();
                },
                'title' => function (CForumPost $post) {
                    return $post->getPostTitle();
                },
                'text' => function (CForumPost $post) {
                    return $post->getPostText();
                },
                'userPoster' => function (CForumPost $post) {
                    $userRepo = $this->em->getRepository('ChamiloUserBundle:User');
                    $user = $userRepo->find($post->getPosterId());

                    return $user;
                },
                'date' => function (CForumPost $post) {
                    return $post->getPostDate();
                },
                'parent' => function (CForumPost $post) {
                    $postRepo = $this->em->getRepository('ChamiloCourseBundle:CForumPost');
                    $parent = $postRepo->find($post->getPostParentId());

                    return $parent;
                },
            ],
            'ToolAgenda' => [
                self::RESOLVE_FIELD => function (
                    CTool $tool,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('events' === $info->fieldName) {
                        $resolver = $this->container->get('chamilo_graphql.resolver.course');

                        return $resolver->getAgenda($context);
                    }

                    return $this->resolveField($info->fieldName, $tool);
                },
            ],
            'CourseAgendaEvent' => [
                'id' => function (array $event) {
                    return $event['unique_id'];
                },
                'description' => function (array $event) {
                    return $event['comment'];
                },
                'startDate' => function (array $event) {
                    return new \DateTime($event['start'], new \DateTimeZone('UTC'));
                },
                'endDate' => function (array $event) {
                    return new \DateTime($event['end'], new \DateTimeZone('UTC'));
                },
            ],
            'ToolDocuments' => [
                'documents' => function (CTool $tool, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    $dirId = !empty($args['dirId']) ? $args['dirId'] : null;

                    return $resolver->getDocuments($dirId, $context);
                },
            ],
            //'CourseDocument' => [],
            'ToolLearningPath' => [
                'categories' => function (CTool $tool, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $resolver->getLearnpathCategories($context);
                },
            ],
            'CourseLearnpathCategory' => [
                'learnpaths' => function (CLpCategory $category, Argument $args, \ArrayObject $context) {
                    $resolver = $this->container->get('chamilo_graphql.resolver.course');

                    return $resolver->getLearnpathsByCategory($category, $context);
                },
            ],
            //'CourseLearnpath' => [],
            'Session' => [
                self::RESOLVE_FIELD => function (
                    Session $session,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('session', $session);
                    $resolver = $this->container->get('chamilo_graphql.resolver.session');

                    return $this->resolveField($info->fieldName, $session, $resolver, $args, $context);
                },
            ],
            'SessionCategory' => [
                self::RESOLVE_FIELD => function (
                    SessionCategory $category,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    if ('startDate' === $info->fieldName) {
                        return $category->getDateStart();
                    }

                    if ('endDate' === $info->fieldName) {
                        return $category->getDateEnd();
                    }

                    return $this->resolveField($info->fieldName, $category);
                },
            ],
        ];
    }

    /**
     * @return User
     */
    protected function resolveViewer()
    {
        $this->checkAuthorization();

        return $this->currentUser;
    }

    /**
     * @param Argument $args
     *
     * @return Course
     */
    protected function resolveCourse(Argument $args)
    {
        $this->checkAuthorization();

        $itemIdInput = array_map('trim', $args['itemId']);

        if (empty($itemIdInput['name']) || empty($itemIdInput['value'])) {
            throw new UserError($this->translator->trans('Missing parameters'));
        }

        $courseId = \CourseManager::get_course_id_from_original_id($itemIdInput['value'], $itemIdInput['name']);
        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $course = $courseRepo->find($courseId);

        if (empty($courseId)) {
            throw new UserError($this->translator->trans("Course not found"));
        }

        if (false === $this->securityChecker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        return $course;
    }

    /**
     * @param Argument $args
     *
     * @return Session
     */
    protected function resolveSession(Argument $args)
    {
        $this->checkAuthorization();

        $itemIdInput = array_map('trim', $args['itemId']);

        if (empty($itemIdInput['name']) || empty($itemIdInput['value'])) {
            throw new UserError($this->translator->trans('Missing parameters'));
        }

        $sessionId = \SessionManager::getSessionIdFromOriginalId($itemIdInput['value'], $itemIdInput['name']);
        $sessionRepo = $this->em->getRepository('ChamiloCoreBundle:Session');
        /** @var Session $session */
        $session = $sessionRepo->find($sessionId);

        if (!$session) {
            throw new UserError($this->translator->trans('Session not found.'));
        }

        if (false === $this->securityChecker->isGranted(SessionVoter::VIEW, $session)) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        return $session;
    }

    /**
     * @param Argument $args
     *
     * @return SessionCategory
     */
    protected function resolveSessionCategory(Argument $args)
    {
        $this->checkAuthorization();

        $repo = $this->em->getRepository('ChamiloCoreBundle:SessionCategory');
        /** @var SessionCategory $category */
        $category = $repo->find($args['id']);

        if (!$category) {
            throw new UserError($this->translator->trans('Session category not found.'));
        }

        return $category;
    }

    /**
     * @param string            $fieldName
     * @param object            $object
     * @param object|null       $resolver
     * @param Argument|null     $args
     * @param \ArrayObject|null $context
     *
     * @return mixed
     */
    private function resolveField(
        $fieldName,
        $object,
        $resolver = null,
        Argument $args = null,
        \ArrayObject $context = null
    ) {
        $method = 'get'.ucfirst($fieldName);

        if ($resolver && $args && $context && method_exists($resolver, $method)) {
            return $resolver->$method($object, $args, $context);
        }

        return $object->$method();
    }
}
