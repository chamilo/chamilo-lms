<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookCertificate.
 *
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/Person",
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      normalizationContext={"groups"={"user:read"}},
 *      denormalizationContext={"groups"={"user:write"}},
 *      collectionOperations={"get"},
 *      itemOperations={
 *          "get"={},
 *          "put"={},
 *          "delete"={},
 *     }
 * )
 * @ORM\Table(
 *     name="gradebook_certificate",
 *     indexes={
 *      @ORM\Index(name="idx_gradebook_certificate_category_id", columns={"cat_id"}),
 *      @ORM\Index(name="idx_gradebook_certificate_user_id", columns={"user_id"}),
 *      @ORM\Index(name="idx_gradebook_certificate_category_id_user_id", columns={"cat_id", "user_id"})}
 * )
 * @ORM\Entity
 */
class GradebookCertificate
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_id", type="integer", nullable=false)
     */
    protected $catId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @deprecated  Use User
     */
    protected $userId;
    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="gradebookCertificate"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }


    /**
     * @var float
     *
     * @ORM\Column(name="score_certificate", type="float", precision=10, scale=0, nullable=false)
     */
    protected $scoreCertificate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="path_certificate", type="text", nullable=true)
     */
    protected $pathCertificate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="downloaded_at", type="datetime", nullable=true)
     */
    protected $downloadedAt;

    /**
     * Set catId.
     *
     * @param int $catId
     *
     * @return GradebookCertificate
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId.
     *
     * @return int
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return GradebookCertificate
     * @deprecated  Use setUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     * @deprecated  Use getUser
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set scoreCertificate.
     *
     * @param float $scoreCertificate
     *
     * @return GradebookCertificate
     */
    public function setScoreCertificate($scoreCertificate)
    {
        $this->scoreCertificate = $scoreCertificate;

        return $this;
    }

    /**
     * Get scoreCertificate.
     *
     * @return float
     */
    public function getScoreCertificate()
    {
        return $this->scoreCertificate;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return GradebookCertificate
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set pathCertificate.
     *
     * @param string $pathCertificate
     *
     * @return GradebookCertificate
     */
    public function setPathCertificate($pathCertificate)
    {
        $this->pathCertificate = $pathCertificate;

        return $this;
    }

    /**
     * Get pathCertificate.
     *
     * @return string
     */
    public function getPathCertificate()
    {
        return $this->pathCertificate;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getDownloadedAt(): \DateTime
    {
        return $this->downloadedAt;
    }

    public function setDownloadedAt(\DateTime $downloadedAt): self
    {
        $this->downloadedAt = $downloadedAt;

        return $this;
    }
}
