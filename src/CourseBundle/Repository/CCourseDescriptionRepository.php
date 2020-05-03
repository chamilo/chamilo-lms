<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Component\Resource\Template;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Form\Resource\CCourseDescriptionType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

final class CCourseDescriptionRepository extends ResourceRepository implements GridInterface
{
    public function getResourceSettings(): Settings
    {
        $settings = parent::getResourceSettings();

        $settings->setAllowResourceCreation(true);

        return $settings;
    }

    public function getTemplates(): Template
    {
        $templates = parent::getTemplates();

        $templates
            ->setViewResource('@ChamiloTheme/Resource/course_description/view_resource.html.twig')
            ->setIndex('@ChamiloTheme/Resource/course_description/index.html.twig');

        return $templates;
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('title');
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        /** @var CCourseDescription $newResource */
        $newResource = $form->getData();

        $newResource
            ->setCId($course->getId())
        ;

        if ($session) {
            $newResource->setSessionId($session->getId());
        }

        return $newResource;
    }

    public function getResourceFormType(): string
    {
        return CCourseDescriptionType::class;
    }
}
