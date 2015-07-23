<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookCertificate
 *
 * @ORM\Table(name="gradebook_certificate", indexes={@ORM\Index(name="idx_gradebook_certificate_category_id", columns={"cat_id"}), @ORM\Index(name="idx_gradebook_certificate_user_id", columns={"user_id"}), @ORM\Index(name="idx_gradebook_certificate_category_id_user_id", columns={"cat_id", "user_id"})})
 * @ORM\Entity
 */
class GradebookCertificate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="cat_id", type="integer", nullable=false)
     */
    private $catId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var float
     *
     * @ORM\Column(name="score_certificate", type="float", precision=10, scale=0, nullable=false)
     */
    private $scoreCertificate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="path_certificate", type="text", nullable=true)
     */
    private $pathCertificate;

    /**
     * Set catId
     *
     * @param integer $catId
     * @return GradebookCertificate
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId
     *
     * @return integer
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return GradebookCertificate
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set scoreCertificate
     *
     * @param float $scoreCertificate
     * @return GradebookCertificate
     */
    public function setScoreCertificate($scoreCertificate)
    {
        $this->scoreCertificate = $scoreCertificate;

        return $this;
    }

    /**
     * Get scoreCertificate
     *
     * @return float
     */
    public function getScoreCertificate()
    {
        return $this->scoreCertificate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return GradebookCertificate
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set pathCertificate
     *
     * @param string $pathCertificate
     * @return GradebookCertificate
     */
    public function setPathCertificate($pathCertificate)
    {
        $this->pathCertificate = $pathCertificate;

        return $this;
    }

    /**
     * Get pathCertificate
     *
     * @return string
     */
    public function getPathCertificate()
    {
        return $this->pathCertificate;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
