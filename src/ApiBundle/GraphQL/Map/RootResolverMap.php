<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class RootResolverMap
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class RootResolverMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    switch ($info->fieldName) {
                        case 'viewer':
                            return $this->resolveViewer();
                        case 'course':
                            return $this->resolveCourse($args['id']);
                        case 'session':
                            $this->resolveSession($args['id']);
                        case 'sessionCategory':
                            return $this->resolveSessionCategory($args['id']);
                    }
                },
            ],
            'UserMessage' => [
                self::RESOLVE_FIELD => function (
                    Message $message,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    switch ($info->fieldName) {
                        case 'sender':
                            return $message->getUserSender();
                        case 'excerpt':
                            $striped = strip_tags($message->getContent());
                            $replaced = str_replace(["\r\n", "\n"], ' ', $striped);
                            $trimmed = trim($replaced);

                            return api_trunc_str($trimmed, $args['length']);
                        case 'hasAttachments':
                            return $message->getAttachments()->count() > 0;
                        default:
                            $method = 'get'.ucfirst($info->fieldName);

                            if (method_exists($message, $method)) {
                                return $message->$method();
                            }

                            return null;
                    }
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

                    switch ($info->fieldName) {
                        case 'picture':
                            return \CourseManager::getPicturePath($course, $args['fullSize']);
                        case 'teachers':
                            if ($context->offsetExists('session')) {
                                /** @var Session $session */
                                $session = $context->offsetGet('session');

                                if ($session) {
                                    $coaches = [];
                                    $coachSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                                        $course,
                                        Session::COACH
                                    );

                                    /** @var SessionRelCourseRelUser $coachSubscription */
                                    foreach ($coachSubscriptions as $coachSubscription) {
                                        $coaches[] = $coachSubscription->getUser();
                                    }

                                    return $coaches;
                                }
                            }

                            $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
                            $teachers = $courseRepo
                                ->getSubscribedTeachers($course)
                                ->getQuery()
                                ->getResult();

                            return $teachers;
                        default:
                            $method = 'get'.ucfirst($info->fieldName);

                            if (method_exists($course, $method)) {
                                return $course->$method();
                            }

                            return null;
                    }
                }
            ],
            'Session' => [
                self::RESOLVE_FIELD => function (
                    Session $session,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    $context->offsetSet('session', $session);

                    switch ($info->fieldName) {
                        case 'description':
                            if (false === $session->getShowDescription()) {
                                return '';
                            }

                            return $session->getDescription();
                        case 'numberOfUsers':
                            return $session->getNbrUsers();
                        case 'numberOfCourses':
                            return $session->getNbrCourses();
                        case 'courses':
                            $authChecker = $this->container->get('security.authorization_checker');
                            $courses = [];

                            /** @var SessionRelCourse $sessionCourse */
                            foreach ($session->getCourses() as $sessionCourse) {
                                $course = $sessionCourse->getCourse();

                                $session->setCurrentCourse($course);

                                if (false !== $authChecker->isGranted(SessionVoter::VIEW, $session)) {
                                    $courses[] = $course;
                                }
                            }

                            return $courses;
                        default:
                            $method = 'get'.ucfirst($info->fieldName);

                            if (method_exists($session, $method)) {
                                return $session->$method();
                            }

                            return null;
                    }
                },
            ],
            'SessionCategory' => [
                self::RESOLVE_FIELD => function (
                    SessionCategory $category,
                    Argument $args,
                    \ArrayObject $context,
                    ResolveInfo $info
                ) {
                    switch ($info->fieldName) {
                        case 'startDate':
                            return $category->getDateStart();
                        case 'endDate':
                            return $category->getDateEnd();
                        default:
                            $method = 'get'.ucfirst($info->fieldName);

                            if (method_exists($category, $method)) {
                                return $category->$method();
                            }

                            return null;
                    }
                }
            ],
        ];
    }

    /**
     * @return User
     */
    private function resolveViewer()
    {
        $this->checkAuthorization();

        return $this->getCurrentUser();
    }

    /**
     * @param int $id
     *
     * @return Course
     */
    private function resolveCourse($id)
    {
        $this->checkAuthorization();

        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $course = $courseRepo->find((int) $id);

        if (!$course) {
            throw new UserError($this->translator->trans('Course not found.'));
        }

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        return $course;
    }

    /**
     * @param int $id
     *
     * @return Session
     */
    private function resolveSession($id)
    {
        $this->checkAuthorization();

        $sessionRepo = $this->em->getRepository('ChamiloCoreBundle:Session');
        /** @var Session $session */
        $session = $sessionRepo->find((int) $id);

        if (!$session) {
            throw new UserError($this->translator->trans('Session not found.'));
        }

        return $session;
    }

    /**
     * @param int $id
     *
     * @return SessionCategory
     */
    private function resolveSessionCategory($id)
    {
        $this->checkAuthorization();

        $repo = $this->em->getRepository('ChamiloCoreBundle:SessionCategory');
        /** @var SessionCategory $category */
        $category = $repo->find((int) $id);

        if (!$category) {
            throw new UserError($this->translator->trans('Session category not found.'));
        }

        return $category;
    }
}
