<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ResourceRepositoryInterface.
 */
interface ResourceRepositoryInterface
{
    public function saveUpload(UploadedFile $file);

    public function saveResource(FormInterface $form, $course, $session, $fileType);

    //public function updateResource(FormInterface $form);
    public function getTitleColumn(Grid $grid): Column;
}
