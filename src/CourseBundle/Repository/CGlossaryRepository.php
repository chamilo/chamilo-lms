<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Component\Utils\ResourceTemplate;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Form\Resource\CGlossaryType;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryGridInterface;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CGlossaryRepository extends ResourceRepository implements ResourceRepositoryGridInterface
{
    public function getResourceSettings(): ResourceSettings
    {
        $settings = parent::getResourceSettings();

        $settings->setAllowResourceCreation(true);

        return $settings;
    }

    public function getTemplates(): ResourceTemplate
    {
        return parent::getTemplates();
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }

    public function saveUpload(UploadedFile $file)
    {
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
        /** @var CGlossary $newResource */
        $newResource = $form->getData();

        $newResource
            ->setCId($course->getId());

        if ($session) {
            $newResource->setSessionId($session->getId());
        }

        return $newResource;
    }

    public function getResourceFormType(): string
    {
        return CGlossaryType::class;
    }
}
