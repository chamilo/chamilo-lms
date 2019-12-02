<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceRepositoryInterface;
use Chamilo\CourseBundle\Entity\CLink;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CLinkRepository.
 */
final class CLinkRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function saveUpload(UploadedFile $file)
    {
        $resource = new CLink();
        /*$resource
            ->setFiletype('file')
            ->setSize($file->getSize())
            ->setTitle($file->getClientOriginalName())
        ;*/

        return $resource;
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
    {
        /** @var CLink $newResource */
        $newResource = $form->getData();
        $newResource
            ->setCId($course->getId())
            ->setDisplayOrder(0)
            ->setOnHomepage(0)
        ;

        return $newResource;
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('title');
    }
}
