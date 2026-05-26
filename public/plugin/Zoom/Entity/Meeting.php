<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingListItem;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use ZoomPlugin;

#[ORM\Entity(repositoryClass: MeetingRepository::class)]
#[ORM\Table(name: 'plugin_zoom_meeting')]
#[ORM\Index(columns: ['user_id'], name: 'user_id_index')]
#[ORM\Index(columns: ['course_id'], name: 'course_id_index')]
#[ORM\Index(columns: ['session_id'], name: 'session_id_index')]
#[ORM\HasLifecycleCallbacks]
class Meeting
{
    /** @var string meeting type name */
    public string $typeName = '';

    /**
     * meeting start time as a DateTime instance
     */
    public ?DateTime $startDateTime = null;

    /** @var string meeting formatted start time */
    public string $formattedStartTime = '';

    /**
     * Meeting duration as a DateInterval instance
     */
    public ?DateInterval $durationInterval = null;

    /** @var string meeting formatted duration */
    public string $formattedDuration = '';

    /** @var string */
    public string $statusName = '';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'meeting_id', type: 'string')]
    protected ?int $meetingId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id')]
    protected ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid')]
    protected ?CGroup $group = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    protected ?Session $session = null;

    #[ORM\Column(name: 'meeting_list_item_json', type: 'text', nullable: true)]
    protected ?string $meetingListItemJson = null;

    #[ORM\Column(name: 'meeting_info_get_json', type: 'text', nullable: true)]
    protected ?string $meetingInfoGetJson = null;

    protected ?MeetingListItem $meetingListItem = null;

    protected ?MeetingInfoGet $meetingInfoGet = null;

    /**
     * @var Collection<int, MeetingActivity>
     */
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[ORM\OneToMany(mappedBy: 'meeting', targetEntity: MeetingActivity::class, cascade: ['persist', 'remove'])]
    protected Collection $activities;

    /**
     * @var Collection<int, Registrant>
     */
    #[ORM\OneToMany(mappedBy: 'meeting', targetEntity: Registrant::class, cascade: ['persist', 'remove'])]
    protected Collection $registrants;

    /**
     * @var Collection<int, Recording>
     */
    #[ORM\OneToMany(mappedBy: 'meeting', targetEntity: Recording::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $recordings;

    public function __construct()
    {
        $this->registrants = new ArrayCollection();
        $this->recordings = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Meeting %d', $this->id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeetingId(): ?int
    {
        return $this->meetingId;
    }

    public function setMeetingId(int $meetingId): static
    {
        $this->meetingId = $meetingId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @return Collection<int, Registrant>
     */
    public function getRegistrants(): Collection
    {
        return $this->registrants;
    }

    /**
     * @return Collection<int, Recording>
     */
    public function getRecordings(): Collection
    {
        return $this->recordings;
    }

    /**
     * @return Collection<int, MeetingActivity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(MeetingActivity $activity): void
    {
        $activity->setMeeting($this);
        $this->activities[] = $activity;
    }

    /**
     * @param Collection<int, MeetingActivity> $activities
     */
    public function setActivities(Collection $activities): static
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @throws Exception
     */
    #[ORM\PostLoad]
    public function postLoad(): void
    {
        if (null !== $this->meetingListItemJson) {
            $this->meetingListItem = MeetingListItem::fromJson($this->meetingListItemJson);
        }
        if (null !== $this->meetingInfoGetJson) {
            $this->meetingInfoGet = MeetingInfoGet::fromJson($this->meetingInfoGetJson);
        }
        $this->initializeDisplayableProperties();
    }

    /**
     * @throws Exception
     */
    #[ORM\PostUpdate]
    public function postUpdate(): void
    {
        $this->initializeDisplayableProperties();
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        if (null !== $this->meetingListItem) {
            $this->meetingListItemJson = json_encode($this->meetingListItem);
        }
        if (null !== $this->meetingInfoGet) {
            $this->meetingInfoGetJson = json_encode($this->meetingInfoGet);
        }
    }

    public function getMeetingListItem(): ?MeetingListItem
    {
        return $this->meetingListItem;
    }

    public function getMeetingInfoGet(): ?MeetingInfoGet
    {
        return $this->meetingInfoGet;
    }

    public function getStatus(): string
    {
        if (null === $this->meetingInfoGet || empty($this->meetingInfoGet->status)) {
            return '';
        }

        return (string) $this->meetingInfoGet->status;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setMeetingListItem(MeetingListItem $meetingListItem): static
    {
        if (null === $this->meetingId) {
            $this->meetingId = $meetingListItem->id;
        } elseif ($this->meetingId != $meetingListItem->id) {
            throw new Exception('the Meeting identifier differs from the MeetingListItem identifier');
        }
        $this->meetingListItem = $meetingListItem;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setMeetingInfoGet(MeetingInfoGet $meetingInfoGet): static
    {
        if (null === $this->meetingId) {
            $this->meetingId = $meetingInfoGet->id;
        } elseif ($this->meetingId != $meetingInfoGet->id) {
            throw new Exception('the Meeting identifier differs from the MeetingInfoGet identifier');
        }
        $this->meetingInfoGet = $meetingInfoGet;
        $this->initializeDisplayableProperties();

        return $this;
    }

    public function isCourseMeeting(): bool
    {
        return null !== $this->course;
    }

    public function isCourseGroupMeeting(): bool
    {
        return null !== $this->course && null !== $this->group;
    }

    public function isUserMeeting(): bool
    {
        return null !== $this->user && null === $this->course;
    }

    public function isGlobalMeeting(): bool
    {
        return null === $this->user && null === $this->course;
    }

    public function setStatus($status): void
    {
        if (null === $this->meetingInfoGet) {
            return;
        }

        $this->meetingInfoGet->status = $status;
        $this->initializeDisplayableProperties();
    }

    /**
     * Builds the list of users that can register into this meeting.
     * Zoom requires an email address, therefore users without an email address are excluded from the list.
     *
     * @return array<int, User> the list of users
     *
     * @throws NotSupported
     */
    public function getRegistrableUsers(): array
    {
        $users = [];
        if (!$this->isCourseMeeting()) {
            $criteria = ['active' => true];
            $users = Database::getManager()->getRepository('ChamiloUserBundle:User')->findBy($criteria);
        } elseif (null === $this->session) {
            if (null !== $this->course) {
                /** @var CourseRelUser $courseRelUser */
                foreach ($this->course->getUsers() as $courseRelUser) {
                    $users[] = $courseRelUser->getUser();
                }
            }
        } elseif (null !== $this->course) {
            $subscriptions = $this->session->getSessionRelCourseRelUsersByStatus($this->course, Session::STUDENT);
            if ($subscriptions->count()) {
                /** @var SessionRelCourseRelUser $sessionCourseUser */
                foreach ($subscriptions as $sessionCourseUser) {
                    $users[] = $sessionCourseUser->getUser();
                }
            }
        }

        $activeUsersWithEmail = [];
        foreach ($users as $user) {
            if ($user->isActive() && !empty($user->getEmail())) {
                $activeUsersWithEmail[] = $user;
            }
        }

        return $activeUsersWithEmail;
    }

    public function requiresDateAndDuration(): bool
    {
        return API\Meeting::TYPE_SCHEDULED === $this->meetingInfoGet->type
            || API\Meeting::TYPE_RECURRING_WITH_FIXED_TIME === $this->meetingInfoGet->type;
    }

    public function requiresRegistration(): bool
    {
        return MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE === $this->meetingInfoGet->settings->approval_type;
        /*return
            MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $this->meetingInfoGet->settings->approval_type;*/
    }

    public function hasCloudAutoRecordingEnabled(): bool
    {
        return ZoomPlugin::RECORDING_TYPE_NONE !== $this->meetingInfoGet->settings->auto_recording;
    }

    public function hasRegisteredUser(User $user): bool
    {
        return $this->getRegistrants()->exists(
            function (Registrant $registrantEntity) use (&$user) {
                return $registrantEntity->getUser() === $user;
            }
        );
    }

    public function getRegistrant(User $user): ?Registrant
    {
        foreach ($this->getRegistrants() as $registrant) {
            if ($registrant->getUser() === $user) {
                return $registrant;
            }
        }

        return null;
    }

    /**
     * Generates a short presentation of the meeting for the future participant.
     * To be displayed above the "Enter meeting" link.
     */
    public function getIntroduction(): string
    {
        $introduction = sprintf('<h1>%s</h1>', $this->meetingInfoGet->topic);
        if (!$this->isGlobalMeeting()) {
            if (!empty($this->formattedStartTime)) {
                $introduction .= $this->formattedStartTime;
                if (!empty($this->formattedDuration)) {
                    $introduction .= ' ('.$this->formattedDuration.')';
                }
            }
        }
        if ($this->user) {
            $introduction .= sprintf('<p>%s</p>', $this->user->getFullName());
        } elseif ($this->isCourseMeeting()) {
            if (null === $this->session) {
                $introduction .= sprintf('<p class="main">%s</p>', $this->course);
            } else {
                $introduction .= sprintf('<p class="main">%s (%s)</p>', $this->course, $this->session);
            }
        }
        if (!empty($this->meetingInfoGet->agenda)) {
            $introduction .= sprintf('<p>%s</p>', $this->meetingInfoGet->agenda);
        }

        return $introduction;
    }

    /**
     * @throws Exception on unexpected start_time or duration
     */
    private function initializeDisplayableProperties(): void
    {
        $this->typeName = '';
        $this->statusName = '';
        $this->startDateTime = null;
        $this->formattedStartTime = '';
        $this->durationInterval = null;
        $this->formattedDuration = '';

        if (null === $this->meetingInfoGet) {
            return;
        }

        $zoomPlugin = new ZoomPlugin();

        $typeList = [
            API\Meeting::TYPE_INSTANT => $zoomPlugin->get_lang('InstantMeeting'),
            API\Meeting::TYPE_SCHEDULED => $zoomPlugin->get_lang('ScheduledMeeting'),
            API\Meeting::TYPE_RECURRING_WITH_NO_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithNoFixedTime'),
            API\Meeting::TYPE_RECURRING_WITH_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithFixedTime'),
        ];

        if (isset($this->meetingInfoGet->type, $typeList[$this->meetingInfoGet->type])) {
            $this->typeName = $typeList[$this->meetingInfoGet->type];
        }

        if (!empty($this->meetingInfoGet->status)) {
            $statusList = [
                'waiting' => $zoomPlugin->get_lang('Waiting'),
                'started' => $zoomPlugin->get_lang('Started'),
                'finished' => $zoomPlugin->get_lang('Finished'),
                'ended' => $zoomPlugin->get_lang('Finished'),
            ];
            $this->statusName = $statusList[$this->meetingInfoGet->status] ?? (string) $this->meetingInfoGet->status;
        }

        if (!empty($this->meetingInfoGet->start_time)) {
            $this->startDateTime = new DateTime($this->meetingInfoGet->start_time);
            $this->startDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $this->formattedStartTime = $this->startDateTime->format('Y-m-d H:i');
        }

        if (!empty($this->meetingInfoGet->duration)) {
            $now = new DateTime();
            $later = new DateTime();
            $later->add(new DateInterval('PT'.$this->meetingInfoGet->duration.'M'));
            $this->durationInterval = $later->diff($now);
            $this->formattedDuration = $this->durationInterval->format($zoomPlugin->get_lang('DurationFormat'));
        }
    }
}
