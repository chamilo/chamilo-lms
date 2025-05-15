<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\SessionTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conference Meeting entity.
 */
#[ORM\Table(name: 'conference_meeting')]
#[ORM\Entity(repositoryClass: ConferenceMeetingRepository::class)]
class ConferenceMeeting
{
    use CourseTrait;
    use SessionTrait;
    use UserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?AccessUrl $accessUrl = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CGroup $group = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(name: 'calendar_id', type: 'integer', nullable: true)]
    protected ?int $calendarId = null;

    #[ORM\Column(name: 'service_provider', type: 'string', length: 20)]
    protected string $serviceProvider = '';

    #[ORM\Column(name: 'remote_id', type: 'string', nullable: true)]
    protected ?string $remoteId = null;

    #[ORM\Column(name: 'internal_meeting_id', type: 'string', nullable: true)]
    protected ?string $internalMeetingId = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title = '';

    #[ORM\Column(name: 'attendee_pw', type: 'string', nullable: true)]
    protected ?string $attendeePw = null;

    #[ORM\Column(name: 'moderator_pw', type: 'string', nullable: true)]
    protected ?string $moderatorPw = null;

    #[ORM\Column(name: 'record', type: 'boolean')]
    protected bool $record = false;

    #[ORM\Column(name: 'status', type: 'integer')]
    protected int $status = 0;

    #[ORM\Column(name: 'welcome_msg', type: 'text', nullable: true)]
    protected ?string $welcomeMsg = null;

    #[ORM\Column(name: 'visibility', type: 'integer')]
    protected int $visibility = 0;

    #[ORM\Column(name: 'voice_bridge', type: 'integer', nullable: true)]
    protected ?int $voiceBridge = null;

    #[ORM\Column(name: 'video_url', type: 'string', nullable: true)]
    protected ?string $videoUrl = null;

    #[ORM\Column(name: 'has_video_m4v', type: 'boolean')]
    protected bool $hasVideoM4v = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'closed_at', type: 'datetime', nullable: true)]
    protected ?DateTime $closedAt = null;

    #[ORM\Column(name: 'meeting_list_item', type: 'text', nullable: true)]
    protected ?string $meetingListItem = null;

    #[ORM\Column(name: 'meeting_info_get', type: 'text', nullable: true)]
    protected ?string $meetingInfoGet = null;

    #[ORM\Column(name: 'sign_attendance', type: 'boolean')]
    protected bool $signAttendance = false;

    #[ORM\Column(name: 'reason_to_sign_attendance', type: 'text', nullable: true)]
    protected ?string $reasonToSignAttendance = null;

    #[ORM\Column(name: 'account_email', type: 'string', length: 255, nullable: true)]
    protected ?string $accountEmail = null;

    #[ORM\Column(name: 'webinar_schema', type: 'text', nullable: true)]
    protected ?string $webinarSchema = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccessUrl(): ?AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(?AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getCalendarId(): ?int
    {
        return $this->calendarId;
    }

    public function setCalendarId(?int $calendarId): self
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    public function getServiceProvider(): string
    {
        return $this->serviceProvider;
    }

    public function setServiceProvider(string $serviceProvider): self
    {
        $this->serviceProvider = $serviceProvider;

        return $this;
    }

    public function getRemoteId(): ?string
    {
        return $this->remoteId;
    }

    public function setRemoteId(?string $remoteId): self
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    public function getInternalMeetingId(): ?string
    {
        return $this->internalMeetingId;
    }

    public function setInternalMeetingId(?string $internalMeetingId): self
    {
        $this->internalMeetingId = $internalMeetingId;

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

    public function getAttendeePw(): ?string
    {
        return $this->attendeePw;
    }

    public function setAttendeePw(?string $attendeePw): self
    {
        $this->attendeePw = $attendeePw;

        return $this;
    }

    public function getModeratorPw(): ?string
    {
        return $this->moderatorPw;
    }

    public function setModeratorPw(?string $moderatorPw): self
    {
        $this->moderatorPw = $moderatorPw;

        return $this;
    }

    public function isRecord(): bool
    {
        return $this->record;
    }

    public function setRecord(bool $record): self
    {
        $this->record = $record;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTime $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getWelcomeMsg(): ?string
    {
        return $this->welcomeMsg;
    }

    public function setWelcomeMsg(?string $welcomeMsg): self
    {
        $this->welcomeMsg = $welcomeMsg;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVoiceBridge(): ?int
    {
        return $this->voiceBridge;
    }

    public function setVoiceBridge(?int $voiceBridge): self
    {
        $this->voiceBridge = $voiceBridge;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function isHasVideoM4v(): bool
    {
        return $this->hasVideoM4v;
    }

    public function setHasVideoM4v(bool $hasVideoM4v): self
    {
        $this->hasVideoM4v = $hasVideoM4v;

        return $this;
    }

    public function getMeetingListItem(): ?string
    {
        return $this->meetingListItem;
    }

    public function setMeetingListItem(?string $meetingListItem): self
    {
        $this->meetingListItem = $meetingListItem;

        return $this;
    }

    public function getMeetingInfoGet(): ?string
    {
        return $this->meetingInfoGet;
    }

    public function setMeetingInfoGet(?string $meetingInfoGet): self
    {
        $this->meetingInfoGet = $meetingInfoGet;

        return $this;
    }

    public function isSignAttendance(): bool
    {
        return $this->signAttendance;
    }

    public function setSignAttendance(bool $signAttendance): self
    {
        $this->signAttendance = $signAttendance;

        return $this;
    }

    public function getAccountEmail(): ?string
    {
        return $this->accountEmail;
    }

    public function setAccountEmail(?string $accountEmail): self
    {
        $this->accountEmail = $accountEmail;

        return $this;
    }

    public function getReasonToSignAttendance(): ?string
    {
        return $this->reasonToSignAttendance;
    }

    public function setReasonToSignAttendance(?string $reasonToSignAttendance): self
    {
        $this->reasonToSignAttendance = $reasonToSignAttendance;

        return $this;
    }

    public function getWebinarSchema(): ?string
    {
        return $this->webinarSchema;
    }

    public function setWebinarSchema(?string $webinarSchema): self
    {
        $this->webinarSchema = $webinarSchema;

        return $this;
    }

    public function isVisible(): bool
    {
        return 1 === $this->visibility;
    }

    public function isClosed(): bool
    {
        return 0 === $this->status;
    }

    public function isOpen(): bool
    {
        return 1 === $this->status;
    }

    public function hasRecording(): bool
    {
        return true === $this->record;
    }

    public function hasVideoUrl(): bool
    {
        return !empty($this->videoUrl);
    }

    public function isRecordingAvailable(): bool
    {
        return $this->hasRecording() && $this->hasVideoUrl();
    }
}
