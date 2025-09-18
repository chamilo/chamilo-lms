<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\LpReorderController;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\LpCollectionStateProvider;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course learning paths (LPs).
 */
#[ApiResource(
    shortName: 'LearningPaths',
    operations: [
        new GetCollection(
            openapiContext: [
                'summary' => 'List learning paths filtered by resourceNode.parent (course) and sid',
                'parameters' => [
                    ['name' => 'resourceNode.parent', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'integer']],
                    ['name' => 'sid', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer']],
                    ['name' => 'title', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                ],
            ],
            name: 'get_lp_collection_with_progress',
            provider: LpCollectionStateProvider::class,
        ),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(
            uriTemplate: '/learning_paths/reorder',
            status: 204,
            controller: LpReorderController::class,
            security: "is_granted('ROLE_TEACHER') or is_granted('ROLE_ADMIN')",
            read: false,
            deserialize: false,
            name: 'lp_reorder'
        ),
    ],
    normalizationContext: [
        'groups' => ['lp:read', 'resource_node:read', 'resource_link:read'],
        'enable_max_depth' => true,
    ],
    denormalizationContext: ['groups' => ['lp:write']],
    paginationEnabled: true,
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'resourceNode.parent' => 'exact',
])]
#[ApiFilter(filterClass: SidFilter::class)]
#[ORM\Table(name: 'c_lp')]
#[ORM\Entity(repositoryClass: CLpRepository::class)]
class CLp extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    public const LP_TYPE = 1;
    public const SCORM_TYPE = 2;
    public const AICC_TYPE = 3;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['lp:read'])]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'lp_type', type: 'integer', nullable: false)]
    protected int $lpType;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    #[Groups(['lp:read', 'lp:write'])]
    protected string $title;

    #[ORM\Column(name: 'ref', type: 'text', nullable: true)]
    protected ?string $ref = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Groups(['lp:read', 'lp:write'])]
    protected ?string $description;

    #[ORM\Column(name: 'path', type: 'text', nullable: false)]
    protected string $path;

    #[ORM\Column(name: 'force_commit', type: 'boolean', nullable: false)]
    protected bool $forceCommit;

    #[ORM\Column(name: 'default_view_mod', type: 'string', length: 32, nullable: false, options: ['default' => 'embedded'])]
    protected string $defaultViewMod;

    #[ORM\Column(name: 'default_encoding', type: 'string', length: 32, nullable: false, options: ['default' => 'UTF-8'])]
    protected string $defaultEncoding;

    #[ORM\Column(name: 'content_maker', type: 'text', nullable: false)]
    protected string $contentMaker;

    #[ORM\Column(name: 'content_local', type: 'string', length: 32, nullable: false, options: ['default' => 'local'])]
    protected string $contentLocal;

    #[ORM\Column(name: 'content_license', type: 'text', nullable: false)]
    protected string $contentLicense;

    #[ORM\Column(name: 'prevent_reinit', type: 'boolean', nullable: false, options: ['default' => 1])]
    protected bool $preventReinit;

    #[ORM\Column(name: 'js_lib', type: 'text', nullable: false)]
    protected string $jsLib;

    #[ORM\Column(name: 'debug', type: 'boolean', nullable: false)]
    protected bool $debug;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'theme', type: 'string', length: 255, nullable: false)]
    protected string $theme;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'author', type: 'text', nullable: false)]
    protected string $author;

    #[ORM\Column(name: 'prerequisite', type: 'integer', nullable: false)]
    protected int $prerequisite;

    #[ORM\Column(name: 'hide_toc_frame', type: 'boolean', nullable: false)]
    protected bool $hideTocFrame;

    #[ORM\Column(name: 'seriousgame_mode', type: 'boolean', nullable: false)]
    protected bool $seriousgameMode;

    #[ORM\Column(name: 'use_max_score', type: 'integer', nullable: false, options: ['default' => 1])]
    protected int $useMaxScore;

    #[ORM\Column(name: 'autolaunch', type: 'integer', nullable: false)]
    protected int $autolaunch;

    #[ORM\ManyToOne(targetEntity: CLpCategory::class, inversedBy: 'lps')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'iid')]
    #[Groups(['lp:read'])]
    #[MaxDepth(1)]
    protected ?CLpCategory $category = null;

    #[ORM\Column(name: 'max_attempts', type: 'integer', nullable: false)]
    protected int $maxAttempts;

    #[ORM\Column(name: 'subscribe_users', type: 'integer', nullable: false)]
    protected int $subscribeUsers;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_on', type: 'datetime', nullable: false)]
    protected DateTime $createdOn;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'modified_on', type: 'datetime', nullable: false)]
    protected DateTime $modifiedOn;

    #[ORM\Column(name: 'published_on', type: 'datetime', nullable: true)]
    #[Groups(['lp:read'])]
    protected ?DateTime $publishedOn;

    #[ORM\Column(name: 'expired_on', type: 'datetime', nullable: true)]
    #[Groups(['lp:read'])]
    protected ?DateTime $expiredOn = null;

    #[ORM\Column(name: 'accumulate_scorm_time', type: 'integer', nullable: false, options: ['default' => 1])]
    protected int $accumulateScormTime = 1;

    #[ORM\Column(name: 'accumulate_work_time', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $accumulateWorkTime = 0;

    #[ORM\Column(name: 'next_lp_id', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $nextLpId = 0;

    #[ORM\Column(name: 'subscribe_user_by_date', type: 'boolean', nullable: false, options: ['default' => 0])]
    protected bool $subscribeUserByDate = false;

    #[ORM\Column(name: 'display_not_allowed_lp', type: 'boolean', nullable: true, options: ['default' => 0])]
    protected bool $displayNotAllowedLp = false;

    #[ORM\OneToMany(mappedBy: 'lp', targetEntity: CLpItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $items;

    /**
     * @var Collection<int, CForum>
     */
    #[ORM\OneToMany(mappedBy: 'lp', targetEntity: CForum::class, cascade: ['persist', 'remove'])]
    protected Collection $forums;

    #[ORM\ManyToOne(targetEntity: Asset::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    protected ?Asset $asset = null;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    protected ?int $duration = null;

    #[ORM\Column(name: 'auto_forward_video', type: 'boolean', options: ['default' => 0])]
    protected bool $autoForwardVideo = false;

    #[Groups(['lp:read'])]
    #[SerializedName('progress')]
    private ?int $progress = null;

    public function __construct()
    {
        $now = new DateTime();
        $this->createdOn = $now;
        $this->modifiedOn = $now;
        $this->publishedOn = $now;
        $this->accumulateScormTime = 1;
        $this->accumulateWorkTime = 0;
        $this->author = '';
        $this->autolaunch = 0;
        $this->contentLocal = 'local';
        $this->contentMaker = 'chamilo';
        $this->contentLicense = '';
        $this->defaultEncoding = 'UTF-8';
        $this->defaultViewMod = 'embedded';
        $this->description = '';
        $this->debug = false;
        $this->forceCommit = false;
        $this->hideTocFrame = false;
        $this->jsLib = '';
        $this->maxAttempts = 0;
        $this->preventReinit = true;
        $this->path = '';
        $this->prerequisite = 0;
        $this->seriousgameMode = false;
        $this->subscribeUsers = 0;
        $this->useMaxScore = 1;
        $this->theme = '';
        $this->nextLpId = 0;
        $this->items = new ArrayCollection();
        $this->forums = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function setLpType(int $lpType): self
    {
        $this->lpType = $lpType;

        return $this;
    }

    public function getLpType(): int
    {
        return $this->lpType;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setRef(string $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setForceCommit(bool $forceCommit): self
    {
        $this->forceCommit = $forceCommit;

        return $this;
    }

    public function getForceCommit(): bool
    {
        return $this->forceCommit;
    }

    public function setDefaultViewMod(string $defaultViewMod): self
    {
        $this->defaultViewMod = $defaultViewMod;

        return $this;
    }

    public function getDefaultViewMod(): string
    {
        return $this->defaultViewMod;
    }

    public function setDefaultEncoding(string $defaultEncoding): self
    {
        $this->defaultEncoding = $defaultEncoding;

        return $this;
    }

    public function getDefaultEncoding(): string
    {
        return $this->defaultEncoding;
    }

    public function setContentMaker(string $contentMaker): self
    {
        $this->contentMaker = $contentMaker;

        return $this;
    }

    public function getContentMaker(): string
    {
        return $this->contentMaker;
    }

    public function setContentLocal(string $contentLocal): self
    {
        $this->contentLocal = $contentLocal;

        return $this;
    }

    public function getContentLocal(): string
    {
        return $this->contentLocal;
    }

    public function setContentLicense(string $contentLicense): self
    {
        $this->contentLicense = $contentLicense;

        return $this;
    }

    public function getContentLicense(): string
    {
        return $this->contentLicense;
    }

    public function setPreventReinit(bool $preventReinit): self
    {
        $this->preventReinit = $preventReinit;

        return $this;
    }

    public function getPreventReinit(): bool
    {
        return $this->preventReinit;
    }

    public function setJsLib(string $jsLib): self
    {
        $this->jsLib = $jsLib;

        return $this;
    }

    public function getJsLib(): string
    {
        return $this->jsLib;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setPrerequisite(int $prerequisite): self
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    public function getPrerequisite(): int
    {
        return $this->prerequisite;
    }

    public function setHideTocFrame(bool $hideTocFrame): self
    {
        $this->hideTocFrame = $hideTocFrame;

        return $this;
    }

    public function getHideTocFrame(): bool
    {
        return $this->hideTocFrame;
    }

    public function setSeriousgameMode(bool $seriousgameMode): self
    {
        $this->seriousgameMode = $seriousgameMode;

        return $this;
    }

    public function getSeriousgameMode(): bool
    {
        return $this->seriousgameMode;
    }

    public function setUseMaxScore(int $useMaxScore): self
    {
        $this->useMaxScore = $useMaxScore;

        return $this;
    }

    public function getUseMaxScore(): int
    {
        return $this->useMaxScore;
    }

    public function setAutolaunch(int $autolaunch): self
    {
        $this->autolaunch = $autolaunch;

        return $this;
    }

    public function getAutolaunch(): int
    {
        return $this->autolaunch;
    }

    public function setCreatedOn(DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    public function setModifiedOn(DateTime $modifiedOn): self
    {
        $this->modifiedOn = $modifiedOn;

        return $this;
    }

    public function getModifiedOn(): DateTime
    {
        return $this->modifiedOn;
    }

    public function setPublishedOn(?DateTime $publishedOn): self
    {
        $this->publishedOn = $publishedOn;

        return $this;
    }

    public function getPublishedOn(): ?DateTime
    {
        return $this->publishedOn;
    }

    public function setExpiredOn(?DateTime $expiredOn): self
    {
        $this->expiredOn = $expiredOn;

        return $this;
    }

    public function getExpiredOn(): ?DateTime
    {
        return $this->expiredOn;
    }

    public function getCategory(): ?CLpCategory
    {
        return $this->category;
    }

    public function hasCategory(): bool
    {
        return null !== $this->category;
    }

    public function setCategory(?CLpCategory $category = null): self
    {
        $this->category = $category;

        return $this;
    }

    public function getAccumulateScormTime(): int
    {
        return $this->accumulateScormTime;
    }

    public function setAccumulateScormTime(int $accumulateScormTime): self
    {
        $this->accumulateScormTime = $accumulateScormTime;

        return $this;
    }

    public function getAccumulateWorkTime(): int
    {
        return $this->accumulateWorkTime;
    }

    public function setAccumulateWorkTime(int $accumulateWorkTime): self
    {
        $this->accumulateWorkTime = $accumulateWorkTime;

        return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getNextLpId(): int
    {
        return $this->nextLpId;
    }

    public function setNextLpId(int $nextLpId): self
    {
        $this->nextLpId = $nextLpId;

        return $this;
    }

    public function getSubscribeUserByDate(): bool
    {
        return $this->subscribeUserByDate;
    }

    public function setSubscribeUserByDate(bool $subscribeUserByDate): self
    {
        $this->subscribeUserByDate = $subscribeUserByDate;

        return $this;
    }

    public function getDisplayNotAllowedLp(): bool
    {
        return $this->displayNotAllowedLp;
    }

    public function setDisplayNotAllowedLp(bool $displayNotAllowedLp): self
    {
        $this->displayNotAllowedLp = $displayNotAllowedLp;

        return $this;
    }

    /**
     * @return Collection<int, CLpItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getSubscribeUsers(): int
    {
        return $this->subscribeUsers;
    }

    public function setSubscribeUsers(int $value): self
    {
        $this->subscribeUsers = $value;

        return $this;
    }

    /**
     * @return Collection<int, CForum>
     */
    public function getForums(): Collection
    {
        return $this->forums;
    }

    public function setForums(ArrayCollection|Collection $forums): self
    {
        $this->forums = $forums;

        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function hasAsset(): bool
    {
        return null !== $this->asset;
    }

    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getAutoForwardVideo(): bool
    {
        return $this->autoForwardVideo;
    }

    public function setAutoForwardVideo(bool $autoForwardVideo): self
    {
        $this->autoForwardVideo = $autoForwardVideo;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress ?? 0;
    }
    public function setProgress(?int $progress): void
    {
        $this->progress = $progress;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
