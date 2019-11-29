<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Symfony\Component\Form\FormInterface;

/**
 * Class ResourceRepositoryInterface.
 */
interface ResourceRepositoryInterface
{
   public function saveResource(FormInterface $form, $course, $session, $fileType);
}
