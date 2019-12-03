<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Component\Utils\ResourceSettings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ResourceRepositoryInterface.
 */
interface ResourceRepositoryInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null);

    public function saveUpload(UploadedFile $file);

    public function saveResource(FormInterface $form, $course, $session, $fileType);

    //public function updateResource(FormInterface $form);
    public function getTitleColumn(Grid $grid): Column;

    //public function getResourceSettings(): ResourceSettings;
}
