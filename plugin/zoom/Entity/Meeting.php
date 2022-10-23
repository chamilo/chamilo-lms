<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\PluginBundle\Zoom\API\BaseMeetingTrait;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingListItem;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\UserBundle\Entity\User;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Class Meeting.
 *
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Zoom\MeetingRepository")
 * @ORM\Table(
 *     name="plugin_zoom_meeting",
 *     indexes={
 *         @ORM\Index(name="user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="course_id_index", columns={"course_id"}),
 *         @ORM\Index(name="session_id_index", columns={"session_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"meeting" = "Chamilo\PluginBundle\Zoom\Meeting", "webinar" = "Chamilo\PluginBundle\Zoom\Webinar"})
 */
class Meeting
{
    /** @var string meeting type name */
    public $typeName;

    /** @var DateTime meeting start time as a DateTime instance */
    public $startDateTime;

    /** @var string meeting formatted start time */
    public $formattedStartTime;

    /** @var DateInterval meeting duration as a DateInterval instance */
    public $durationInterval;

    /** @var string meeting formatted duration */
    public $formattedDuration;

    /** @var string */
    public $statusName;

    /**
     * @var int
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var int the remote zoom meeting identifier
     * @ORM\Column(name="meeting_id", type="string")
     */
    protected $meetingId;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var Course
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", nullable=true)
     */
    protected $course;

    /**
     * @var CGroupInfo
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true)
     */
    protected $group;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_list_item_json", nullable=true)
     */
    protected $meetingListItemJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_info_get_json", nullable=true)
     */
    protected $meetingInfoGetJson;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="sign_attendance")
     */
    protected $signAttendance;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", name="reason_to_sign_attendance", nullable=true)
     */
    protected $reasonToSignAttendance;

    /** @var MeetingListItem */
    protected $meetingListItem;

    /** @var MeetingInfoGet */
    protected $meetingInfoGet;

    /**
     * @var MeetingActivity[]|ArrayCollection
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @ORM\OneToMany(targetEntity="MeetingActivity", mappedBy="meeting", cascade={"persist", "remove"})
     */
    protected $activities;

    /**
     * @var Registrant[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Registrant", mappedBy="meeting", cascade={"persist", "remove"})
     */
    protected $registrants;

    /**
     * @var Recording[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Recording", mappedBy="meeting", cascade={"persist"}, orphanRemoval=true)
     */
    protected $recordings;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="account_email", nullable=true)
     */
    protected $accountEmail;

    /**
     * @var SysAnnouncement|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\SysAnnouncement")
     * @ORM\JoinColumn(name="sys_announcement_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $sysAnnouncement;

    public function __construct()
    {
        $this->registrants = new ArrayCollection();
        $this->recordings = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->signAttendance = false;
        $this->sysAnnouncement = null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Meeting %d', $this->id);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMeetingId()
    {
        return $this->meetingId;
    }

    /**
     * @param int $meetingId
     *
     * @return Meeting
     */
    public function setMeetingId($meetingId)
    {
        $this->meetingId = $meetingId;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return Registrant[]|ArrayCollection
     */
    public function getRegistrants()
    {
        return $this->registrants;
    }

    /**
     * @return Recording[]|ArrayCollection
     */
    public function getRecordings()
    {
        return $this->recordings;
    }

    /**
     * @return MeetingActivity[]|ArrayCollection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    public function addActivity(MeetingActivity $activity)
    {
        $activity->setMeeting($this);
        $this->activities[] = $activity;
    }

    /**
     * @param MeetingActivity[]|ArrayCollection $activities
     *
     * @return Meeting
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * @ORM\PostLoad
     *
     * @throws Exception
     */
    public function postLoad()
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
     * @ORM\PostUpdate
     *
     * @throws Exception
     */
    public function postUpdate()
    {
        $this->initializeDisplayableProperties();
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlush()
    {
        if (null !== $this->meetingListItem) {
            $this->meetingListItemJson = json_encode($this->meetingListItem);
        }
        if (null !== $this->meetingInfoGet) {
            $this->meetingInfoGetJson = json_encode($this->meetingInfoGet);
        }
    }

    /**
     * @return MeetingListItem
     */
    public function getMeetingListItem()
    {
        return $this->meetingListItem;
    }

    /**
     * @return MeetingInfoGet
     */
    public function getMeetingInfoGet()
    {
        return $this->meetingInfoGet;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @param Session $session
     *
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return CGroupInfo
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param CGroupInfo $group
     *
     * @return Meeting
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @param MeetingListItem $meetingListItem
     *
     * @throws Exception
     *
     * @return Meeting
     */
    public function setMeetingListItem($meetingListItem)
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
     * @param MeetingInfoGet|BaseMeetingTrait $meetingInfoGet
     *
     * @throws Exception
     *
     * @return Meeting
     */
    public function setMeetingInfoGet($meetingInfoGet)
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

    /**
     * @return bool
     */
    public function isCourseMeeting()
    {
        return null !== $this->course;
    }

    /**
     * @return bool
     */
    public function isCourseGroupMeeting()
    {
        return null !== $this->course && null !== $this->group;
    }

    /**
     * @return bool
     */
    public function isUserMeeting()
    {
        return null !== $this->user && null === $this->course;
    }

    /**
     * @return bool
     */
    public function isGlobalMeeting()
    {
        return null === $this->user && null === $this->course;
    }

    public function setStatus($status)
    {
        $this->meetingInfoGet->status = $status;
    }

    /**
     * Builds the list of users that can register into this meeting.
     * Zoom requires an email address, therefore users without an email address are excluded from the list.
     *
     * @return User[] the list of users
     */
    public function getRegistrableUsers()
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
        } else {
            if (null !== $this->course) {
                $subscriptions = $this->session->getUserCourseSubscriptionsByStatus($this->course, Session::STUDENT);
                if ($subscriptions) {
                    /** @var SessionRelCourseRelUser $sessionCourseUser */
                    foreach ($subscriptions as $sessionCourseUser) {
                        $users[] = $sessionCourseUser->getUser();
                    }
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

    /**
     * @return bool
     */
    public function requiresDateAndDuration()
    {
        return MeetingInfoGet::TYPE_SCHEDULED === $this->meetingInfoGet->type
            || MeetingInfoGet::TYPE_RECURRING_WITH_FIXED_TIME === $this->meetingInfoGet->type;
    }

    public function requiresRegistration(): bool
    {
        return true; //MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE === $this->meetingInfoGet->settings->approval_type;
        /*return
            MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $this->meetingInfoGet->settings->approval_type;*/
    }

    /**
     * @return bool
     */
    public function hasCloudAutoRecordingEnabled()
    {
        return \ZoomPlugin::RECORDING_TYPE_NONE !== $this->meetingInfoGet->settings->auto_recording;
    }

    public function getRegistrantByUser(User $user): ?Registrant
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('user', $user)
            )
        ;

        return $this->registrants->matching($criteria)->first() ?: null;
    }

    /**
     * Generates a short presentation of the meeting for the future participant.
     * To be displayed above the "Enter meeting" link.
     *
     * @return string
     */
    public function getIntroduction()
    {
        $introduction = sprintf('<h1>%s</h1>', $this->getTopic()).PHP_EOL;
        if (!$this->isGlobalMeeting()) {
            if (!empty($this->formattedStartTime)) {
                $introduction .= $this->formattedStartTime;
                if (!empty($this->formattedDuration)) {
                    $introduction .= ' ('.$this->formattedDuration.')';
                }
                $introduction .= PHP_EOL;
            }
        }
        if ($this->user) {
            $introduction .= sprintf('<p>%s</p>', $this->user->getFullname()).PHP_EOL;
        } elseif ($this->isCourseMeeting()) {
            if (null === $this->session) {
                $introduction .= sprintf('<p class="main">%s</p>', $this->course).PHP_EOL;
            } else {
                $introduction .= sprintf('<p class="main">%s (%s)</p>', $this->course, $this->session).PHP_EOL;
            }
        }

        if (!empty($this->getAgenda())) {
            $introduction .= sprintf('<p>%s</p>', $this->getAgenda()).PHP_EOL;
        }

        return $introduction;
    }

    public function isSignAttendance(): bool
    {
        return $this->signAttendance;
    }

    public function setSignAttendance(bool $signAttendance): Meeting
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

    public function setReasonToSignAttendance(string $reasonToSignAttendance): Meeting
    {
        $this->reasonToSignAttendance = $reasonToSignAttendance;

        return $this;
    }

    public function getTopic(): string
    {
        return $this->meetingInfoGet->topic;
    }

    public function getAgenda(): ?string
    {
        return $this->meetingInfoGet->agenda;
    }

    public function getSysAnnouncement(): ?SysAnnouncement
    {
        return $this->sysAnnouncement;
    }

    public function setSysAnnouncement(?SysAnnouncement $sysAnnouncement): Meeting
    {
        $this->sysAnnouncement = $sysAnnouncement;

        return $this;
    }

    /**
     * @throws Exception on unexpected start_time or duration
     */
    protected function initializeDisplayableProperties()
    {
        $zoomPlugin = new \ZoomPlugin();

        $typeList = [
            API\Meeting::TYPE_INSTANT => $zoomPlugin->get_lang('InstantMeeting'),
            API\Meeting::TYPE_SCHEDULED => $zoomPlugin->get_lang('ScheduledMeeting'),
            API\Meeting::TYPE_RECURRING_WITH_NO_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithNoFixedTime'),
            API\Meeting::TYPE_RECURRING_WITH_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithFixedTime'),
        ];
        $this->typeName = $typeList[$this->meetingInfoGet->type];

        if (property_exists($this, 'status')) {
            $statusList = [
                'waiting' => $zoomPlugin->get_lang('Waiting'),
                'started' => $zoomPlugin->get_lang('Started'),
                'finished' => $zoomPlugin->get_lang('Finished'),
            ];
            $this->statusName = $statusList[$this->meetingInfoGet->status];
        }
        $this->startDateTime = null;
        $this->formattedStartTime = '';
        $this->durationInterval = null;
        $this->formattedDuration = '';
        if (!empty($this->meetingInfoGet->start_time)) {
            $this->startDateTime = new DateTime($this->meetingInfoGet->start_time);
            $this->startDateTime->setTimezone(new DateTimeZone(api_get_timezone()));
            $this->formattedStartTime = $this->startDateTime->format('Y-m-d H:i');
        }

        if (!empty($this->meetingInfoGet->duration)) {
            $now = new DateTime();
            $later = new DateTime();
            $later->add(new DateInterval('PT'.$this->meetingInfoGet->duration.'M'));
            $this->durationInterval = $now->diff($later);
            $this->formattedDuration = $this->durationInterval->format($zoomPlugin->get_lang('DurationFormat'));
        }
    }
}
