<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;

#[ORM\Table(name: 'gradebook_certificate')]
#[ORM\Index(columns: ['user_id'], name: 'idx_gradebook_certificate_user_id')]
#[ORM\Entity(repositoryClass: GradebookCertificateRepository::class)]
class GradebookCertificate extends AbstractResource implements ResourceInterface, Stringable
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GradebookCategory::class)]
    #[ORM\JoinColumn(name: 'cat_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?GradebookCategory $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'gradeBookCertificates')]
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

    #[ORM\Column(name: 'publish', type: 'boolean', options: ['default' => false])]
    protected bool $publish = false;

    public function __toString(): string
    {
        $user = isset($this->user) ? $this->user->getUsername() : 'user';
        $when = isset($this->createdAt) ? $this->createdAt->format('Y-m-d H:i') : 'pending';

        return "Certificate for {$user} ({$when})";
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return (string) $this;
    }

    public function setResourceName(string $name)
    {
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCategory(?GradebookCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?GradebookCategory
    {
        return $this->category;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setScoreCertificate(float $scoreCertificate): self
    {
        $this->scoreCertificate = $scoreCertificate;

        return $this;
    }

    public function getScoreCertificate(): float
    {
        return $this->scoreCertificate;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setPathCertificate(?string $pathCertificate): self
    {
        $this->pathCertificate = $pathCertificate;

        return $this;
    }

    public function getPathCertificate(): ?string
    {
        return $this->pathCertificate;
    }

    public function getDownloadedAt(): ?DateTime
    {
        return $this->downloadedAt;
    }

    public function setDownloadedAt(?DateTime $downloadedAt): self
    {
        $this->downloadedAt = $downloadedAt;

        return $this;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getPublish(): bool
    {
        return $this->publish;
    }
}
