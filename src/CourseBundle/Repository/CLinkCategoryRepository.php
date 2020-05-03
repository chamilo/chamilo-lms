<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Form\Resource\CLinkType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

/**
 * Class CLinkCategoryRepository.
 */
final class CLinkCategoryRepository extends ResourceRepository implements GridInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getResourceSettings(): Settings
    {
        $settings = parent::getResourceSettings();

        $settings->setAllowResourceCreation(true);

        return $settings;
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        /** @var CLink $newResource */
        $newResource = $form->getData();
        $newResource
            ->setCId($course->getId())
            ->setDisplayOrder(0)
            ->setOnHomepage(0)
        ;

        return $newResource;
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('title');
    }

    public function getResourceFormType(): string
    {
        return CLinkType::class;
    }
}
