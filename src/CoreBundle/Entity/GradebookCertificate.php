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
#[ORM\Index(columns: ['expiry_date'], name: 'idx_gradebook_certificate_expiry_date')]
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

    /**
     * Calendar date the certificate stops being valid, or null if it never expires
     * (either the category has no certificateValidityPeriod, or a teacher cleared
     * a manually-set date). Always a UTC calendar date: computed as
     * createdAt (UTC) + certificateValidityPeriod days, with no further timezone
     * conversion at read time. Do not compare it against a locally-timezoned
     * "now" — use UTC "today" for expiry checks.
     */
    #[ORM\Column(name: 'expiry_date', type: 'date', nullable: true)]
    protected ?DateTime $expiryDate = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return (string) $this;
    }

    public function setResourceName(string $name)
    {
        return $this;
    }

    public function getCategory(): ?GradebookCategory
    {
        return $this->category;
    }

    public function setCategory(?GradebookCategory $category): self
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

    public function getScoreCertificate(): float
    {
        return $this->scoreCertificate;
    }

    public function setScoreCertificate(float $scoreCertificate): self
    {
        $this->scoreCertificate = $scoreCertificate;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPathCertificate(): ?string
    {
        return $this->pathCertificate;
    }

    public function setPathCertificate(?string $pathCertificate): self
    {
        $this->pathCertificate = $pathCertificate;

        return $this;
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

    public function getPublish(): bool
    {
        return $this->publish;
    }

    public function setPublish(bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?DateTime $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }
}
