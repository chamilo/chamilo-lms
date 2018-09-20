<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseAnnouncementResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseAnnouncementResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param array        $item
     * @param Argument     $args
     * @param ResolveInfo  $info
     * @param \ArrayObject $context
     *
     * @return mixed
     */
    public function __invoke(array $item, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        /** @var CAnnouncement $announcement */
        $announcement = $item['announcement'];
        /** @var CItemProperty $itemProperty */
        $itemProperty = $item['item_property'];
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($announcement, $itemProperty, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($announcement, $method)) {
            return $announcement->$method();
        }

        if (method_exists($itemProperty, $method)) {
            return $itemProperty->$method();
        }

        return null;
    }

    /**
     * @param CAnnouncement $announcement
     *
     * @return int
     */
    public function resolveId(CAnnouncement $announcement)
    {
        return $announcement->getIid();
    }

    /**
     * @param CAnnouncement $announcement
     * @param CItemProperty $itemProperty
     *
     * @return User
     */
    public function resolveBy(CAnnouncement $announcement, CItemProperty $itemProperty)
    {
        return $itemProperty->getInsertUser();
    }

    /**
     * @param CAnnouncement $announcement
     * @param CItemProperty $itemProperty
     *
     * @return \DateTime
     */
    public function resolveLastUpdateDate(CAnnouncement $announcement, CItemProperty $itemProperty)
    {
        return $itemProperty->getLasteditDate();
    }
}