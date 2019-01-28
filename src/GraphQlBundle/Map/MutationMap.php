<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MutationMap.
 *
 * @package Chamilo\GraphQlBundle\Map
 */
class MutationMap extends ResolverMap implements ContainerAwareInterface
{
    use GraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'Mutation' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    $method = 'resolve'.ucfirst($info->fieldName);

                    return $this->$method($args, $context);
                },
            ],
        ];
    }

    /**
     * @param Argument $args
     *
     * @return array
     */
    protected function resolveAuthenticate(Argument $args)
    {
        /** @var User $user */
        $user = $this->em->getRepository('ChamiloUserBundle:User')->findOneBy(['username' => $args['username']]);

        if (!$user) {
            throw new UserError($this->translator->trans('User not found.'));
        }

        $encoder = $this->container->get('security.password_encoder');
        $isValid = $encoder->isPasswordValid($user, $args['password']);

        if (!$isValid) {
            throw new UserError($this->translator->trans('Password is not valid.'));
        }

        return [
            'token' => $this->encodeToken($user),
        ];
    }

    /**
     * @param Argument $args
     *
     * @return array
     */
    protected function resolveViewerSendMessage(Argument $args)
    {
        $this->checkAuthorization();

        $currentUser = $this->getCurrentUser();
        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($currentUser->getId());
        $receivers = array_filter(
            $args['receivers'],
            function ($receiverId) use ($users) {
                /** @var User $user */
                foreach ($users as $user) {
                    if ($user->getId() === (int) $receiverId) {
                        return true;
                    }
                }

                return false;
            }
        );

        $result = [];

        foreach ($receivers as $receiverId) {
            $messageId = \MessageManager::send_message(
                $receiverId,
                $args['subject'],
                $args['text'],
                [],
                [],
                0,
                0,
                0,
                0,
                $currentUser->getId()
            );

            $result[] = [
                'receiverId' => $receiverId,
                'sent' => (bool) $messageId,
            ];
        }

        return $result;
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return Course
     */
    protected function resolveCreateCourse(Argument $args): ?Course
    {
        $this->checkAuthorization();

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $course = $args['course'];
        $originalCourseIdName = $args['originalCourseIdName'];
        $originalCourseIdValue = $args['originalCourseIdValue'];

        $title = $course['title'];
        $categoryCode = !empty($course['categoryCode']) ? $course['categoryCode'] : null;
        $wantedCode = isset($course['wantedCode']) ? $course['wantedCode'] : null;
        $language = $course['language'];
        $visibility = isset($course['visibility']) ? $course['visibility'] : COURSE_VISIBILITY_OPEN_PLATFORM;
        $diskQuota = $course['diskQuota'] * 1024 * 1024;
        $allowSubscription = $course['allowSubscription'];
        $allowUnsubscription = $course['allowUnsubscription'];

        $courseInfo = \CourseManager::getCourseInfoFromOriginalId($originalCourseIdValue, $originalCourseIdName);

        if (!empty($courseInfo)) {
            throw new UserError($this->translator->trans('Course already exists'));
        }

        $currentUser = $this->getCurrentUser();

        $params = [
            'title' => $title,
            'wanted_code' => $wantedCode,
            'category_code' => $categoryCode,
            //'tutor_name',
            'course_language' => $language,
            'user_id' => $currentUser->getId(),
            'visibility' => $visibility,
            'disk_quota' => $diskQuota,
            'subscribe' => !empty($allowSubscription),
            'unsubscribe' => !empty($allowUnsubscription),
        ];

        $courseInfo = \CourseManager::create_course($params, $currentUser->getId());

        if (empty($courseInfo)) {
            throw new UserError($this->translator->trans('Course not created'));
        }

        \CourseManager::create_course_extra_field(
            $originalCourseIdName,
            \ExtraField::FIELD_TYPE_TEXT,
            $originalCourseIdName
        );

        \CourseManager::update_course_extra_field_value(
            $courseInfo['code'],
            $originalCourseIdName,
            $originalCourseIdValue
        );

        return $this->em->find('ChamiloCoreBundle:Course', $courseInfo['real_id']);
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return User|null
     */
    protected function resolveCreateUser(Argument $args): ?User
    {
        $this->checkAuthorization();

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $userInput = $args['user'];
        $originalUserIdName = $args['originalUserIdName'];
        $originalUserIdValue = $args['originalUserIdValue'];

        $userId = \UserManager::get_user_id_from_original_id($originalUserIdValue, $originalUserIdName);

        if (!empty($userId)) {
            throw new UserError($this->translator->trans('User already exists'));
        }

        if (!\UserManager::is_username_available($userInput['username'])) {
            throw new UserError($this->translator->trans('Username already exists'));
        }

        $language = !empty($userInput['language']) ? $userInput['language'] : null;
        $phone = !empty($userInput['phone']) ? $userInput['phone'] : null;
        $expirationDate = !empty($userInput['expirationDate'])
            ? $userInput['expirationDate']->format('Y-m-d h:i:s')
            : null;

        $userId = \UserManager::create_user(
            $userInput['firstname'],
            $userInput['lastname'],
            $userInput['status'],
            $userInput['email'],
            $userInput['username'],
            $userInput['password'],
            null,
            $language,
            $phone,
            null,
            PLATFORM_AUTH_SOURCE,
            $expirationDate,
            $userInput['isActive']
        );

        if (empty($userId)) {
            throw new UserError($this->translator->trans('User not created'));
        }

        \UrlManager::add_user_to_url($userId);
        \UserManager::create_extra_field($originalUserIdName, \ExtraField::FIELD_TYPE_TEXT, $originalUserIdName, '');
        \UserManager::update_extra_field_value($userId, $originalUserIdName, $originalUserIdValue);

        return $this->em->find('ChamiloUserBundle:User', $userId);
    }
}
