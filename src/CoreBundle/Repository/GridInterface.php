<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;

interface GridInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null);

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, $fileType);

    public function getTitleColumn(Grid $grid): Column;

    public function getResourceFormType(): string;

    public function getResourceSettings(): Settings;
}
