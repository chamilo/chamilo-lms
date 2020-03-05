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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CChatConversationRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getResourceSettings(): ResourceSettings
    {
        $settings = new ResourceSettings();
        $settings
            ->setAllowNodeCreation(false)
            ->setAllowResourceCreation(false)
            ->setAllowResourceUpload(false)
        ;

        return $settings;
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function saveUpload(UploadedFile $file)
    {
        throw new AccessDeniedException();
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }

    public function getResourceFormType(): string
    {
        return FormType::class;
    }
}
