<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'gradebook_certificate')]
#[ORM\Index(name: 'idx_gradebook_certificate_user_id', columns: ['user_id'])]
#[ORM\Entity]
class GradebookCertificate
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\GradebookCategory::class)]
    #[ORM\JoinColumn(name: 'cat_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected GradebookCategory $category;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'gradeBookCertificates')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'score_certificate', type: 'float', precision: 10, scale: 0, nullable: false)]
    protected float $scoreCertificate;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'path_certificate', type: 'text', nullable: true)]
    protected ?string $pathCertificate = null;

    #[ORM\Column(name: 'downloaded_at', type: 'datetime', nullable: true)]
    protected ?DateTime $downloadedAt = null;

    public function setScoreCertificate(float $scoreCertificate): self
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

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setPathCertificate(string $pathCertificate): self
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

    public function getDownloadedAt(): DateTime
    {
        return $this->downloadedAt;
    }

    public function setDownloadedAt(DateTime $downloadedAt): self
    {
        $this->downloadedAt = $downloadedAt;

        return $this;
    }

    public function getCategory(): GradebookCategory
    {
        return $this->category;
    }

    public function setCategory(GradebookCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
