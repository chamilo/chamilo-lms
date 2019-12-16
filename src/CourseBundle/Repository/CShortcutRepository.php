<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CDocumentRepository.
 */
final class CShortcutRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null)
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getResourceSettings(): ResourceSettings
    {
        $settings = parent::getResourceSettings();

        $settings
            ->setAllowNodeCreation(false)
            ->setAllowResourceCreation(true)
            ->setAllowResourceUpload(false)
        ;

        return $settings;
    }

    public function saveUpload(UploadedFile $file)
    {
        return false;
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
        $newResource = $form->getData();
        $newResource
            ->setCourse($course)
            ->setSession($session)
            ->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
            ->setReadonly(false)
        ;

        return $newResource;
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }
}
