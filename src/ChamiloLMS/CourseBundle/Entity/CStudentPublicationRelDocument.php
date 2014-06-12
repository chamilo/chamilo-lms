<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationRelDocument
 *
 * @ORM\Table(name="c_student_publication_rel_document")
 * @ORM\Entity
 */
class CStudentPublicationRelDocument
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="work_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $workId;

    /**
     * @var integer
     *
     * @ORM\Column(name="document_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $documentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;


    /**
     * Get iid
     *
     * @return integer 
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set workId
     *
     * @param integer $workId
     * @return CStudentPublicationRelDocument
     */
    public function setWorkId($workId)
    {
        $this->workId = $workId;

        return $this;
    }

    /**
     * Get workId
     *
     * @return integer 
     */
    public function getWorkId()
    {
        return $this->workId;
    }

    /**
     * Set documentId
     *
     * @param integer $documentId
     * @return CStudentPublicationRelDocument
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;

        return $this;
    }

    /**
     * Get documentId
     *
     * @return integer 
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CStudentPublicationRelDocument
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }
}
