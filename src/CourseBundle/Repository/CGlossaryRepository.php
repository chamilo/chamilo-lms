<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Component\Resource\Template;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Resource\CGlossaryType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

final class CGlossaryRepository extends ResourceRepository implements GridInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CGlossary::class);
    }

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
            ->setViewResource('@ChamiloCore/Resource/glossary/view_resource.html.twig')
        ;

        return $templates;
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
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
