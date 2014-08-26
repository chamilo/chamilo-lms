<?php

namespace Chamilo\NotebookBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * Class CNotebookRepository
 * @package Chamilo\NotebookBundle\Entity
 */
class CNotebookRepository extends EntityRepository
{
    /**
     * @param Course $course
     * @return mixed
     */
    public function createNewWithCourse(Course $course)
    {
        $notebook = parent::createNew();
        $notebook->setCourse($course);

        return $notebook;
        //$notebook->save();

        //var_dump($course);
    }
}
