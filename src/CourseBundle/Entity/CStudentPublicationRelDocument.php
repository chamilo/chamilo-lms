<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationRelDocument.
 *
 * @ORM\Table(
 *     name="c_student_publication_rel_document",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="work", columns={"work_id"}),
 *         @ORM\Index(name="document", columns={"document_id"})
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="work_id", type="integer", nullable=false)
     */
    protected int $workId;

    /**
     * @ORM\Column(name="document_id", type="integer", nullable=false)
     */
    protected int $documentId;

    /**
     * Set workId.
     *
     * @param int $workId
     *
     * @return CStudentPublicationRelDocument
     */
    public function setWorkId($workId)
    {
        $this->workId = $workId;

        return $this;
    }

    /**
     * Get workId.
     *
     * @return int
     */
    public function getWorkId()
    {
        return $this->workId;
    }

    /**
     * Set documentId.
     *
     * @param int $documentId
     *
     * @return CStudentPublicationRelDocument
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;

        return $this;
    }

    /**
     * Get documentId.
     *
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CStudentPublicationRelDocument
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
