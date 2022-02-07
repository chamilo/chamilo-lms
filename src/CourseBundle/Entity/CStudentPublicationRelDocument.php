<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="c_student_publication_rel_document",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CStudentPublicationRelDocument
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CStudentPublication")
     * @ORM\JoinColumn(name="work_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CStudentPublication $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CDocument")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CDocument $document;

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function setPublication(CStudentPublication $publication): self
    {
        $this->publication = $publication;

        return $this;
    }

    public function getDocument(): CDocument
    {
        return $this->document;
    }

    public function setDocument(CDocument $document): self
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
