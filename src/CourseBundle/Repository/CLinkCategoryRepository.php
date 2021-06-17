<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Resource\CLinkType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

final class CLinkCategoryRepository extends ResourceRepository implements GridInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLinkCategory::class);
    }

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }*/

    public function getResourceSettings(): Settings
    {
        $settings = parent::getResourceSettings();

        $settings->setAllowResourceCreation(true);

        return $settings;
    }

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, string $fileType): void
    {
        /** @var CLink $newResource */
        $newResource = $form->getData();
        $newResource->setDisplayOrder(0)
        ;
        //return $newResource;
    }

    public function getResourceFormType(): string
    {
        return CLinkType::class;
    }
}
