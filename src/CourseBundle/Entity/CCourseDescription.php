<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_course_description')]
#[ORM\Entity(repositoryClass: CCourseDescriptionRepository::class)]
class CCourseDescription extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    public const TYPE_DESCRIPTION = 1;
    public const TYPE_OBJECTIVES = 2;
    public const TYPE_TOPICS = 3;
    public const TYPE_METHODOLOGY = 4;
    public const TYPE_COURSE_MATERIAL = 5;
    public const TYPE_RESOURCES = 6;
    public const TYPE_ASSESSMENT = 7;
    public const TYPE_CUSTOM = 8;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected ?string $content;

    #[Assert\Choice(callback: 'getTypes')]
    #[ORM\Column(name: 'description_type', type: 'integer', nullable: false)]
    protected int $descriptionType;

    #[ORM\Column(name: 'progress', type: 'integer', nullable: false)]
    protected int $progress;

    /**
     * Runtime flag to skip search indexing (not persisted).
     */
    private bool $skipSearchIndex = false;

    public function __construct()
    {
        $this->content = '';
        $this->progress = 0;
        $this->descriptionType = 1;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_DESCRIPTION,
            self::TYPE_OBJECTIVES,
            self::TYPE_TOPICS,
            self::TYPE_METHODOLOGY,
            self::TYPE_COURSE_MATERIAL,
            self::TYPE_RESOURCES,
            self::TYPE_ASSESSMENT,
            self::TYPE_CUSTOM,
        ];
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    public function setDescriptionType(int $descriptionType): self
    {
        $this->descriptionType = $descriptionType;

        return $this;
    }

    /**
     * @return int
     */
    public function getDescriptionType()
    {
        return $this->descriptionType;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return (string) $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    /**
     * Mark this entity to skip search indexing in the current request.
     */
    public function setSkipSearchIndex(bool $skip): self
    {
        $this->skipSearchIndex = $skip;

        return $this;
    }

    /**
     * Should search indexing be skipped for this entity in this request?
     */
    public function shouldSkipSearchIndex(): bool
    {
        return $this->skipSearchIndex;
    }
}
