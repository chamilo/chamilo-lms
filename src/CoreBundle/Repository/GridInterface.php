<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;

interface GridInterface
{
    //public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder;

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, string $fileType): void;

    public function getResourceFormType(): string;

    public function getResourceSettings(): Settings;
}
