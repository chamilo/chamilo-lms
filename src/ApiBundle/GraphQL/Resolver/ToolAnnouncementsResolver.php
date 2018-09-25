<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CTool;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ToolAnnouncementsResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class ToolAnnouncementsResolver implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getAnnouncements(CTool $tool, \ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = null;

        if ($context->offsetExists('session')) {
            $session = $context->offsetGet('session');
        }

        $em = $this->container->get('chamilo_course.entity.manager.announcement_manager');

        try {
            $announcementsInfo = $em->getAnnouncements(
                $this->getCurrentUser(),
                $course,
                null,
                $session,
                api_get_course_setting('allow_user_edit_announcement') === 'true',
                api_get_configuration_value('hide_base_course_announcements_in_group') === true
            );
        } catch (\Exception $exception) {
            throw new UserError($exception->getMessage());
        }

        if (empty($announcementsInfo)) {
            return [];
        }

        $announcements = [];

        for ($z = 0; $z < count($announcementsInfo); $z += 2) {
            $announcements[] = [
                'announcement' => $announcementsInfo[$z],
                'item_property' => $announcementsInfo[$z + 1],
            ];
        }

        return $announcements;
    }
}
