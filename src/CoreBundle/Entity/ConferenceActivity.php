<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\VideoConferenceCallbackController;
use Chamilo\CoreBundle\Repository\ConferenceActivityRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conference Activity entity.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/videoconference/callback',
            controller: VideoConferenceCallbackController::class,
            read: false,
            deserialize: false,
            validate: false
        ),
    ]
)]
#[ORM\Entity(repositoryClass: ConferenceActivityRepository::class)]
#[ORM\Table(name: 'conference_activity')]
class ConferenceActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: ConferenceMeeting::class)]
    #[ORM\JoinColumn(name: 'meeting_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ConferenceMeeting $meeting = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $participant = null;

    #[ORM\Column(name: 'in_at', type: 'datetime', nullable: true)]
    protected ?DateTime $inAt = null;

    #[ORM\Column(name: 'out_at', type: 'datetime', nullable: true)]
    protected ?DateTime $outAt = null;

    #[ORM\Column(name: 'close', type: 'boolean')]
    protected bool $close = false;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    protected string $type = '';

    #[ORM\Column(name: 'event', type: 'string', length: 255)]
    protected string $event = '';

    #[ORM\Column(name: 'activity_data', type: 'text', nullable: true)]
    protected ?string $activityData = null;

    #[ORM\Column(name: 'signature_file', type: 'string', length: 255, nullable: true)]
    protected ?string $signatureFile = null;

    #[ORM\Column(name: 'signed_at', type: 'datetime', nullable: true)]
    protected ?DateTime $signedAt = null;

    /** Stores per-user analytics for the meeting (dashboard metrics). */
    #[ORM\Column(name: 'metrics', type: 'json', nullable: true)]
    protected ?array $metrics = null;

    public function __construct()
    {
        $this->close = false;
        $this->type = '';
        $this->event = '';
        $this->activityData = null;
        $this->signatureFile = null;
        $this->inAt = new DateTime();
        $this->outAt = null;
        $this->signedAt = null;
        $this->metrics = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMeeting(): ?ConferenceMeeting
    {
        return $this->meeting;
    }

    public function setMeeting(?ConferenceMeeting $meeting): self
    {
        $this->meeting = $meeting;

        return $this;
    }

    public function getParticipant(): ?User
    {
        return $this->participant;
    }

    public function setParticipant(?User $participant): self
    {
        $this->participant = $participant;

        return $this;
    }

    public function getInAt(): ?DateTime
    {
        return $this->inAt;
    }

    public function setInAt(?DateTime $inAt): self
    {
        $this->inAt = $inAt;

        return $this;
    }

    public function getOutAt(): ?DateTime
    {
        return $this->outAt;
    }

    public function setOutAt(?DateTime $outAt): self
    {
        $this->outAt = $outAt;

        return $this;
    }

    public function isClose(): bool
    {
        return $this->close;
    }

    public function setClose(bool $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getActivityData(): ?string
    {
        return $this->activityData;
    }

    public function setActivityData(?string $activityData): self
    {
        $this->activityData = $activityData;

        return $this;
    }

    public function getSignatureFile(): ?string
    {
        return $this->signatureFile;
    }

    public function setSignatureFile(?string $signatureFile): self
    {
        $this->signatureFile = $signatureFile;

        return $this;
    }

    public function getSignedAt(): ?DateTime
    {
        return $this->signedAt;
    }

    public function setSignedAt(?DateTime $signedAt): self
    {
        $this->signedAt = $signedAt;

        return $this;
    }

    /**
     * Returns the full metrics array (never null to simplify callers).
     * Keep in mind: this method does NOT create defaults; it just returns stored data.
     */
    public function getMetrics(): array
    {
        return $this->metrics ?? [];
    }

    /**
     * Replaces the entire metrics array. Null or empty arrays will store NULL to keep DB small.
     */
    public function setMetrics(?array $metrics): self
    {
        $this->metrics = $metrics ? $this->pruneEmpty($metrics) : null;

        return $this;
    }

    /** Read a value from metrics by dot path, with a default if missing. */
    public function getMetric(string $path, mixed $default = null): mixed
    {
        $data = $this->getMetrics();
        foreach (explode('.', $path) as $seg) {
            if (!is_array($data) || !array_key_exists($seg, $data)) {
                return $default;
            }
            $data = $data[$seg];
        }

        return $data;
    }

    /** Set a value into metrics by dot path, creating nested arrays as needed. */
    public function setMetric(string $path, mixed $value): self
    {
        $metrics = $this->getMetrics();
        $ref =& $metrics;

        $parts = $path === '' ? [] : explode('.', $path);
        foreach ($parts as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref =& $ref[$seg];
        }

        $ref = $value;

        return $this->setMetrics($metrics);
    }

    /** Increment an integer metric by dot path (initializes to 0 if missing). */
    public function incMetric(string $path, int $by = 1): self
    {
        $current = (int) $this->getMetric($path, 0);

        return $this->setMetric($path, $current + $by);
    }

    /**
     * Start a named timer: stores ISO timestamp under "timers.{key}.on_at".
     * Timer is idempotent (won't overwrite if already running).
     */
    public function startTimer(string $key, ?\DateTimeInterface $now = null): self
    {
        $now ??= new \DateTimeImmutable();

        if (!$this->getMetric("timers.$key.on_at")) {
            $this->setMetric("timers.$key.on_at", $now->format(DATE_ATOM));
        }

        return $this;
    }

    /**
     * Stop a named timer and add elapsed seconds to "totals.{key}_seconds".
     * If the timer is not running, this is a no-op.
     */
    public function stopTimer(string $key, ?\DateTimeInterface $now = null): self
    {
        $now ??= new \DateTimeImmutable();
        $onAt = $this->getMetric("timers.$key.on_at");

        if ($onAt) {
            $started = \DateTimeImmutable::createFromFormat(DATE_ATOM, $onAt) ?: new \DateTimeImmutable($onAt);
            $elapsed = max(0, $now->getTimestamp() - $started->getTimestamp());

            $this->incMetric("totals.{$key}_seconds", $elapsed);
            $this->setMetric("timers.$key.on_at", null);
        }

        return $this;
    }

    /**
     * Internal helper to remove null/empty leaves so JSON column stays lean.
     * This is called from setMetrics().
     */
    private function pruneEmpty(array $data): array
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $v = $this->pruneEmpty($v);
                if ($v === []) {
                    unset($data[$k]);
                    continue;
                }
                $data[$k] = $v;
            } elseif ($v === null || $v === '') {
                unset($data[$k]);
            }
        }

        return $data;
    }
}
