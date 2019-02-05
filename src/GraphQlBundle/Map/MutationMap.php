<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CNotebook;
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
            'urlId' => $this->currentAccessUrl->getId(),
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

        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($this->currentUser->getId());
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
                $this->currentUser->getId()
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
    protected function resolveCreateCourse(Argument $args): Course
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $course = $args['course'];

        $title = $course['title'];
        $categoryCode = !empty($course['categoryCode']) ? $course['categoryCode'] : null;
        $wantedCode = isset($course['wantedCode']) ? $course['wantedCode'] : null;
        $language = $course['language'];
        $visibility = isset($course['visibility']) ? $course['visibility'] : COURSE_VISIBILITY_OPEN_PLATFORM;
        $diskQuota = $course['diskQuota'] * 1024 * 1024;
        $allowSubscription = $course['allowSubscription'];
        $allowUnsubscription = $course['allowUnsubscription'];

        $courseInfo = \CourseManager::getCourseInfoFromOriginalId($args['itemId']['value'], $args['itemId']['name']);

        if (!empty($courseInfo)) {
            throw new UserError($this->translator->trans('Course already exists'));
        }

        $params = [
            'title' => $title,
            'wanted_code' => $wantedCode,
            'category_code' => $categoryCode,
            //'tutor_name',
            'course_language' => $language,
            'user_id' => $this->currentUser->getId(),
            'visibility' => $visibility,
            'disk_quota' => $diskQuota,
            'subscribe' => !empty($allowSubscription),
            'unsubscribe' => !empty($allowUnsubscription),
        ];

        $courseInfo = \CourseManager::create_course(
            $params,
            $this->currentUser->getId(),
            $this->currentAccessUrl->getId()
        );

        if (empty($courseInfo)) {
            throw new UserError($this->translator->trans('Course not created'));
        }

        \CourseManager::create_course_extra_field(
            $args['itemId']['name'],
            \ExtraField::FIELD_TYPE_TEXT,
            $args['itemId']['name']
        );

        \CourseManager::update_course_extra_field_value(
            $courseInfo['code'],
            $args['itemId']['name'],
            $args['itemId']['value']
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
     * @return Course
     */
    protected function resolveEditCourse(Argument $args): Course
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $courseInput = array_map('trim', $args['course']);
        $itemIdInput = array_map('trim', $args['itemId']);

        if (empty($itemIdInput['name']) || empty($itemIdInput['value'])) {
            throw new UserError($this->translator->trans('Missing parameters'));
        }

        $courseId = \CourseManager::get_course_id_from_original_id($itemIdInput['value'], $itemIdInput['name']);

        if (empty($courseId)) {
            throw new UserError($this->translator->trans("Course doesn't exists"));
        }

        /** @var Course $course */
        $course = $this->em->find('ChamiloCoreBundle:Course', $courseId);

        if (!empty($courseInput['title'])) {
            $course->setTitle($courseInput['title']);
        }

        if (isset($courseInput['categoryCode'])) {
            $course->setCategoryCode($courseInput['categoryCode']);
        }

        if (!empty($courseInput['visualCode'])) {
            $course->setVisualCode($courseInput['visualCode']);
        }

        if (!empty($courseInput['language'])) {
            $course->setCourseLanguage($courseInput['language']);
        }

        if (!empty($courseInput['diskQuota'])) {
            $course->setDiskQuota($courseInput['diskQuota'] * 1024 * 1024);
        }

        if (isset($courseInput['visibility'])) {
            $course->setVisibility($courseInput['visibility']);
        }

        if ($course->getSubscribe() !== $courseInput['allowSubscription']) {
            $course->setSubscribe($courseInput['allowSubscription']);
        }

        if ($course->getUnsubscribe() !== $courseInput['allowUnsubscription']) {
            $course->setUnsubscribe($courseInput['allowUnsubscription']);
        }

        $course->setCurrentUrl($this->currentAccessUrl);

        $this->em->persist($course);
        $this->em->flush();

        return $course;
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return User
     */
    protected function resolveCreateUser(Argument $args): User
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $userInput = $args['user'];

        $userId = \UserManager::get_user_id_from_original_id($args['itemId']['value'], $args['itemId']['name']);

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

        \UrlManager::add_user_to_url(
            $userId,
            $this->currentAccessUrl->getId()
        );
        \UserManager::create_extra_field($args['itemId']['name'], \ExtraField::FIELD_TYPE_TEXT, $args['itemId']['name'], '');
        \UserManager::update_extra_field_value($userId, $args['itemId']['name'], $args['itemId']['value']);

        return $this->em->find('ChamiloUserBundle:User', $userId);
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return bool
     */
    protected function resolveSubscribeUserToCourse(Argument $args): bool
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        /** @var User $user */
        $user = $this->em->find('ChamiloUserBundle:User', $args['user']);
        /** @var Course $course */
        $course = $this->em->find('ChamiloCoreBundle:Course', $args['course']);

        if (null === $user) {
            throw new UserError($this->translator->trans('User not found'));
        }

        if (null === $course) {
            throw new UserError($this->translator->trans('Course not found'));
        }

        if (!\UrlManager::relation_url_user_exist($user->getId(), $this->currentAccessUrl->getId())) {
            throw new UserError($this->translator->trans('User not registered in this URL'));
        }

        if (!\UrlManager::relation_url_course_exist($course->getId(), $this->currentAccessUrl->getId())) {
            throw new UserError($this->translator->trans('Course not registered in this URL'));
        }

        $isSubscribed = \CourseManager::subscribeUser($user->getId(), $course->getCode());

        return $isSubscribed;
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return CForumThread
     */
    protected function resolveAddForumThread(Argument $args): CForumThread
    {
        $this->checkAuthorization();

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        /** @var Course $course */
        $course = $this->em->find('ChamiloCoreBundle:Course', $args['course']);
        /** @var Session|null $session */
        $session = null;

        if (!$course) {
            throw new UserError($this->translator->trans('Course not found'));
        }

        if (!empty($args['session'])) {
            $session = $this->em->find('ChamiloCoreBundle:Session', $args['session']);

            if (!$session) {
                throw new UserError($this->translator->trans('Session not found'));
            }
        }

        $this->checkCourseAccess($course, $session);

        $forumInfo = get_forums(
            $args['thread']['forum'],
            $course->getCode(),
            true,
            $session ? $session->getId() : 0
        );
        $forumCategoryInfo = get_forumcategory_information($forumInfo['forum_category']);

        if (
            ($forumCategoryInfo && 0 == $forumCategoryInfo['visibility']) ||
            0 == $forumInfo['visibility']
        ) {
            throw new UserError('Not allowed');
        }

        if (
            ($forumCategoryInfo['visibility'] && 0 != $forumCategoryInfo['locked']) ||
            0 != $forumInfo['locked']
        ) {
            throw new UserError('Not allowed');
        }

        if (1 != $forumInfo['allow_new_threads']) {
            throw new UserError('Not allowed');
        }

        if (0 != $forumInfo['forum_of_group']) {
            $showForum = \GroupManager::user_has_access(
                $this->currentUser->getId(),
                $forumInfo['forum_of_group'],
                \GroupManager::GROUP_TOOL_FORUM
            );

            if (!$showForum) {
                throw new UserError('Not allowed');
            }
        }

        $courseInfo = api_get_course_info($course->getCode());

        $threadId = store_thread(
            $forumInfo,
            [
                'post_title' => $args['thread']['title'],
                'forum_id' => $args['thread']['forum'],
                'post_text' => nl2br($args['thread']['text']),
                'post_notification' => $args['notify'],
            ],
            $courseInfo,
            false,
            $this->currentUser->getId(),
            $session ? $session->getId() : 0
        );

        $threadRepo = $this->em->getRepository('ChamiloCourseBundle:CForumThread');
        $thread = $threadRepo->findOneInCourse($threadId, $course, $session);

        return $thread;
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return CForumPost
     */
    protected function resolveAddForumPost(Argument $args): CForumPost
    {
        $this->checkAuthorization();

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        /** @var Course $course */
        $course = $this->em->find('ChamiloCoreBundle:Course', $args['course']);
        /** @var Session|null $session */
        $session = null;

        if (!$course) {
            throw new UserError($this->translator->trans('Course not found'));
        }

        if (!empty($args['session'])) {
            $session = $this->em->find('ChamiloCoreBundle:Session', $args['session']);

            if (!$session) {
                throw new UserError($this->translator->trans('Session not found'));
            }
        }

        $this->checkCourseAccess($course, $session);

        $threadInfo = get_thread_information(
            $args['post']['forum'],
            $args['post']['thread'],
            $session ? $session->getId() : 0
        );
        $forumInfo = get_forums(
            $args['post']['forum'],
            $course->getCode(),
            true,
            $session ? $session->getId() : 0
        );
        $forumCategoryInfo = get_forumcategory_information($forumInfo['forum_category']);

        if (
            ($forumCategoryInfo && 0 == $forumCategoryInfo['visibility']) ||
            0 == $forumInfo['visibility']
        ) {
            throw new UserError('Not allowed');
        }

        if (
            ($forumCategoryInfo && 0 != $forumCategoryInfo['locked']) ||
            0 != $forumInfo['locked'] || 0 != $threadInfo['locked']
        ) {
            throw new UserError('Not allowed');
        }

        if (1 != $forumInfo['allow_new_threads']) {
            throw new UserError('Not allowed');
        }

        if (0 != $forumInfo['forum_of_group']) {
            $showForum = \GroupManager::user_has_access(
                $this->currentUser->getId(),
                $forumInfo['forum_of_group'],
                \GroupManager::GROUP_TOOL_FORUM
            );

            if (!$showForum) {
                throw new UserError('Not allowed');
            }
        }

        $postId = store_reply(
            $forumInfo,
            [
                'post_title' => $args['post']['title'],
                'post_text' => nl2br($args['post']['text']),
                'thread_id' => $args['post']['thread'],
                'post_notification' => $args['post']['notify'],
                'post_parent_id' => $args['post']['parent'],
            ],
            $course->getId(),
            $this->currentUser->getId()
        );

        return $this->em->find('ChamiloCourseBundle:CForumPost', $postId);
    }

    /**
     * @param Argument $args
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return CNotebook
     */
    protected function resolveAddCourseNote(Argument $args): CNotebook
    {
        $this->checkAuthorization();

        /** @var Course $course */
        $course = $this->em->find('ChamiloCoreBundle:Course', $args['course']);
        /** @var Session|null $session */
        $session = null;

        if (!$course) {
            throw new UserError($this->translator->trans('Course not found'));
        }

        if (!empty($args['session'])) {
            $session = $this->em->find('ChamiloCoreBundle:Session', $args['session']);

            if (!$session) {
                throw new UserError($this->translator->trans('Session not found'));
            }
        }

        $this->checkCourseAccess($course, $session);

        $noteId = \NotebookManager::save_note(
            [
                'note_title' => $args['title'],
                'note_comment' => $args['text'],
            ],
            $this->currentUser->getId(),
            $course->getId(),
            $session ? $session->getId() : 0
        );

        return $this->em->find('ChamiloCourseBundle:CNotebook', $noteId);
    }

    /**
     * @param Argument $args
     *
     * @return bool
     */
    protected function resolveDisableUser(Argument $args): bool
    {
        $this->changeUserActiveState($args['itemId']['name'], $args['itemId']['value'], false);

        return true;
    }

    /**
     * @param Argument $args
     *
     * @return bool
     */
    protected function resolveEnableUser(Argument $args): bool
    {
        $this->changeUserActiveState($args['itemId']['name'], $args['itemId']['value'], true);

        return true;
    }

    /**
     * @param Argument $args
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function resolveDeleteUser(Argument $args): bool
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $userId = \UserManager::get_user_id_from_original_id($args['itemId']['value'], $args['itemId']['name']);

        if (empty($userId)) {
            throw new UserError($this->translator->trans('User not found'));
        }

        return \UserManager::delete_user($userId);
    }

    /**
     * @param Argument $args
     *
     * @return User
     */
    protected function resolveEditUser(Argument $args): User
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $userInput = $args['user'];

        $userId = \UserManager::get_user_id_from_original_id($args['itemId']['value'], $args['itemId']['name']);

        if (!empty($userId)) {
            throw new UserError($this->translator->trans('User already exists'));
        }

        /** @var User $user */
        $user = $this->em->find('ChamiloUserBundle:User', $userId);

        \UserManager::update_user(
            $userId,
            !empty($userInput['firstname']) ? $userInput['firstname'] : $user->getFirstname(),
            !empty($userInput['lastname']) ? $userInput['lastname'] : $user->getLastname(),
            !empty($userInput['username']) ? $userInput['username'] : $user->getUsername(),
            null,
            PLATFORM_AUTH_SOURCE,
            !empty($userInput['email']) ? $userInput['email'] : $user->getEmail(),
            !empty($userInput['status']) ? $userInput['status'] : $user->getStatus(),
            !empty($userInput['officialCode']) ? $userInput['officialCode'] : $user->getOfficialCode(),
            !empty($userInput['phone']) ? $userInput['phone'] : $user->getPhone(),
            $user->getPictureUri(),
            !empty($userInput['expirationDate']) ? $userInput['expirationDate'] : $user->getExpirationDate(),
            !empty($userInput['isActive']) ? $userInput['isActive'] : $user->isActive()
        );

        $this->em->clear($user);

        return $this->em->find('ChamiloUserBundle:User', $userId);
    }

    /**
     * @param Argument $args
     *
     * @return bool
     */
    protected function resolveDeleteCourse(Argument $args): bool
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $itemIdInput = array_map('trim', $args['itemId']);

        if (empty($itemIdInput['name']) || empty($itemIdInput['value'])) {
            throw new UserError($this->translator->trans('Missing parameters'));
        }

        $courseInfo = \CourseManager::getCourseInfoFromOriginalId($itemIdInput['value'], $itemIdInput['name']);

        if (empty($courseInfo)) {
            throw new UserError($this->translator->trans("Course doesn't exists"));
        }

        \CourseManager::delete_course($courseInfo['code']);

        return true;
    }

    /**
     * @param string $userIdName
     * @param string $userIdValue
     * @param bool   $setActive
     */
    private function changeUserActiveState($userIdName, $userIdValue, $setActive)
    {
        $this->checkAuthorization();

        if (false === $this->securityChecker->isGranted('ROLE_ADMIN')) {
            throw new UserError($this->translator->trans('Not allowed'));
        }

        $userId = \UserManager::get_user_id_from_original_id($userIdValue, $userIdName);

        if (empty($userId)) {
            throw new UserError($this->translator->trans('User not found'));
        }

        if ($setActive) {
            \UserManager::enable($userId);
        } else {
            \UserManager::disable($userId);
        }
    }
}
