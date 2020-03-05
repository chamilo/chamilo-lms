<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Form\Resource\CLinkType;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CLinkRepository.
 */
final class CLinkRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getResourceSettings(): ResourceSettings
    {
        $settings = parent::getResourceSettings();

        $settings->setAllowResourceCreation(true);

        return $settings;
    }

    public function saveUpload(UploadedFile $file)
    {
        return new CLink();
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
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
