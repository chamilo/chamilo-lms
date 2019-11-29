<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CLink;
use Symfony\Component\Form\FormInterface;

/**
 * Class CLinkRepository.
 */
final class CLinkRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
        /** @var CLink $newResource */
        $newResource = $form->getData();
        $newResource
            ->setCId($course->getId())
            ->setDisplayOrder(0)
            ->setOnHomepage(0)
        ;
            //$newResource->setSessionId($session)
        /*$newResource
            ->setCourse($course)
            ->setSession($session)*/
            //->setTitle($title) // already added in $form->getData()
        ;

        return $newResource;
    }
}
