<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class UserResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class UserResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases(): array
    {
        return [
            'resolveUserPicture' => 'user_picture',
            'resolveEmail' => 'user_email',
            'resolveUserMessages' => 'user_messages',
            'resolveMessageContacts' => 'user_message_contacts',
            'resolveCourses' => 'user_courses',
        ];
    }

    /**
     * @param User $user
     * @param int  $size
     *
     * @return string
     */
    public function resolveUserPicture(User $user, $size): string
    {
        $assets = $this->container->get('templating.helper.assets');
        $path = $user->getAvatarOrAnonymous((int) $size);

        return $assets->getUrl($path);
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return string
     */
    public function resolveEmail(User $user, \ArrayObject $context)
    {
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($user->getId() === $contextUser->getId()) {
            return $user->getEmail();
        }

        $settingsManager = $this->container->get('chamilo.settings.manager');
        $showEmail = $settingsManager->getSetting('display.show_email_addresses') === 'true';

        if (!$showEmail) {
            return '';
        }

        return $user->getEmail();
    }

    /**
     * @param User         $user
     * @param int          $lastId
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveUserMessages(User $user, $lastId = 0, \ArrayObject $context): array
    {
        $this->protectUserData($context, $user);

        /** @var MessageRepository $messageRepo */
        $messageRepo = $this->em->getRepository('ChamiloCoreBundle:Message');
        $messages = $messageRepo->getFromLastOneReceived($user, (int) $lastId);

        return $messages;
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveCourses(User $user, \ArrayObject $context)
    {
        $this->protectUserData($context, $user);

        $courses = [];
        $coursesInfo = \CourseManager::get_courses_list_by_user_id($user->getId());
        $coursesRepo = $this->em->getRepository('ChamiloCoreBundle:Course');

        foreach ($coursesInfo as $courseInfo) {
            /** @var Course $course */
            $course = $coursesRepo->find($courseInfo['real_id']);

            if ($course) {
                $courses[] = $course;
            }
        }

        return $courses;
    }

    /**
     * @param User         $user
     * @param string       $filter
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveMessageContacts(User $user, $filter, \ArrayObject $context): array
    {
        $this->protectUserData($context, $user);

        if (strlen($filter) < 3) {
            return [];
        }

        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($user->getId(), $filter);

        return $users;
    }
}
