<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'languageId' => 'exact',
    'version' => 'exact',
    'type' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'version',
    'type',
    'date',
    'languageId',
])]
#[ORM\Table(name: 'legal')]
#[ORM\Entity(repositoryClass: LegalRepository::class)]
class Legal
{
    // Terms section types (0 = main terms, 1..15 = GDPR privacy terms).
    public const TYPE_TERMS = 0;

    public const TYPE_PRIVACY_COLLECTION = 1;
    public const TYPE_PRIVACY_RECORDING = 2;
    public const TYPE_PRIVACY_ORGANIZATION = 3;
    public const TYPE_PRIVACY_STRUCTURE = 4;
    public const TYPE_PRIVACY_CONSERVATION = 5;
    public const TYPE_PRIVACY_ADAPTATION = 6;
    public const TYPE_PRIVACY_EXTRACTION = 7;
    public const TYPE_PRIVACY_CONSULTATION = 8;
    public const TYPE_PRIVACY_USAGE = 9;
    public const TYPE_PRIVACY_COMMUNICATION = 10;
    public const TYPE_PRIVACY_INTERCONNECTION = 11;
    public const TYPE_PRIVACY_LIMITATION = 12;
    public const TYPE_PRIVACY_DELETION = 13;
    public const TYPE_PRIVACY_DESTRUCTION = 14;
    public const TYPE_PRIVACY_PROFILING = 15;

    public const MIN_TYPE = self::TYPE_TERMS;
    public const MAX_TYPE = self::TYPE_PRIVACY_PROFILING;

    public const VALID_TYPES = [
        self::TYPE_TERMS,
        self::TYPE_PRIVACY_COLLECTION,
        self::TYPE_PRIVACY_RECORDING,
        self::TYPE_PRIVACY_ORGANIZATION,
        self::TYPE_PRIVACY_STRUCTURE,
        self::TYPE_PRIVACY_CONSERVATION,
        self::TYPE_PRIVACY_ADAPTATION,
        self::TYPE_PRIVACY_EXTRACTION,
        self::TYPE_PRIVACY_CONSULTATION,
        self::TYPE_PRIVACY_USAGE,
        self::TYPE_PRIVACY_COMMUNICATION,
        self::TYPE_PRIVACY_INTERCONNECTION,
        self::TYPE_PRIVACY_LIMITATION,
        self::TYPE_PRIVACY_DELETION,
        self::TYPE_PRIVACY_DESTRUCTION,
        self::TYPE_PRIVACY_PROFILING,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $date = 0;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $content = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $type = self::TYPE_TERMS;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'text')]
    protected string $changes = '';

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $version = null;

    #[Groups(['legal:read', 'legal:write'])]
    #[ORM\Column(type: 'integer')]
    protected int $languageId = 0;

    public static function isValidType(int $type): bool
    {
        return \in_array($type, self::VALID_TYPES, true);
    }

    public static function assertValidType(int $type): void
    {
        if (!self::isValidType($type)) {
            throw new InvalidArgumentException(\sprintf('Invalid legal type "%d". Allowed range is %d..%d.', $type, self::MIN_TYPE, self::MAX_TYPE));
        }
    }

    public static function isPrivacyType(int $type): bool
    {
        return $type >= self::TYPE_PRIVACY_COLLECTION && $type <= self::TYPE_PRIVACY_PROFILING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get date.
     */
    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        self::assertValidType($type);
        $this->type = $type;

        return $this;
    }

    public function getChanges(): string
    {
        return $this->changes;
    }

    public function setChanges(string $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function setLanguageId(int $languageId): self
    {
        $this->languageId = $languageId;

        return $this;
    }
}
