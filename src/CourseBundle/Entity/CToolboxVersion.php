<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_toolbox_version')]
#[ORM\UniqueConstraint(name: 'uniq_toolbox_version_number', columns: ['toolbox_id', 'version_number'])]
#[ORM\Entity]
class CToolboxVersion
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CToolbox::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(name: 'toolbox_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CToolbox $toolbox;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: CLp::class)]
    #[ORM\JoinColumn(name: 'learning_path_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CLp $learningPath = null;

    #[ORM\Column(name: 'version_number', type: 'integer', nullable: false)]
    protected int $versionNumber = 1;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'prompt', type: 'text', nullable: true)]
    protected ?string $prompt = null;

    #[ORM\Column(name: 'provider', type: 'string', length: 64, nullable: true)]
    protected ?string $provider = null;

    #[ORM\Column(name: 'change_summary', type: 'text', nullable: true)]
    protected ?string $changeSummary = null;

    #[ORM\Column(name: 'html_content', type: 'text', nullable: true)]
    protected ?string $htmlContent = null;

    #[ORM\Column(name: 'css_content', type: 'text', nullable: true)]
    protected ?string $cssContent = null;

    #[ORM\Column(name: 'javascript_content', type: 'text', nullable: true)]
    protected ?string $javascriptContent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToolbox(): CToolbox
    {
        return $this->toolbox;
    }

    public function setToolbox(CToolbox $toolbox): self
    {
        $this->toolbox = $toolbox;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getLearningPath(): ?CLp
    {
        return $this->learningPath;
    }

    public function setLearningPath(?CLp $learningPath): self
    {
        $this->learningPath = $learningPath;

        return $this;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): self
    {
        $this->versionNumber = max(1, $versionNumber);

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = null !== $provider && '' !== trim($provider) ? trim($provider) : null;

        return $this;
    }

    public function getChangeSummary(): ?string
    {
        return $this->changeSummary;
    }

    public function setChangeSummary(?string $changeSummary): self
    {
        $this->changeSummary = null !== $changeSummary && '' !== trim($changeSummary) ? trim($changeSummary) : null;

        return $this;
    }

    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(?string $htmlContent): self
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    public function getCssContent(): ?string
    {
        return $this->cssContent;
    }

    public function setCssContent(?string $cssContent): self
    {
        $this->cssContent = $cssContent;

        return $this;
    }

    public function getJavascriptContent(): ?string
    {
        return $this->javascriptContent;
    }

    public function setJavascriptContent(?string $javascriptContent): self
    {
        $this->javascriptContent = $javascriptContent;

        return $this;
    }

    public function hasGeneratedSource(): bool
    {
        return '' !== trim((string) $this->htmlContent)
            || '' !== trim((string) $this->cssContent)
            || '' !== trim((string) $this->javascriptContent);
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
}
