<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;

final class PersonalFileRepository extends ResourceRepository implements GridInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalFile::class);
    }

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCreator($user, $parentNode);
    }*/

    public function getResourceSettings(): Settings
    {
        $settings = parent::getResourceSettings();

        $settings
            ->setAllowNodeCreation(true)
            //->setAllowResourceCreation(true)
            ->setAllowResourceUpload(true)
            ->setAllowResourceEdit(false)
        ;

        return $settings;
    }

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, string $fileType): void
    {
        //return $form->getData();

        //->setCourse($course)
            //->setSession($session)
            //->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
    }
}
