<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Symfony\Component\Form\FormInterface;

interface GridInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null);

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, $fileType);

    public function getResourceFormType(): string;

    public function getResourceSettings(): Settings;
}
