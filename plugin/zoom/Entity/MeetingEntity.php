<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingListItem;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\UserBundle\Entity\User;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Class MeetingEntity.
 *
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Zoom\MeetingEntityRepository")
 * @ORM\Table(
 *     name="plugin_zoom_meeting",
 *     indexes={
 *         @ORM\Index(name="user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="course_id_index", columns={"course_id"}),
 *         @ORM\Index(name="session_id_index", columns={"session_id"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class MeetingEntity
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
     * @var int the remote zoom meeting identifier
     * @ORM\Column(type="bigint")
     * @ORM\Id
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\UserBundle\Entity\User",
     * )
     * @ORM\JoinColumn(name="user_id", nullable=true)
     */
    private $user;

    /**
     * @var Course
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Course",
     * )
     * @ORM\JoinColumn(name="course_id", nullable=true)
     */
    private $course;

    /**
     * @var Session
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Session",
     * )
     * @ORM\JoinColumn(name="session_id", nullable=true)
     */
    private $session;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_list_item_json", nullable=true)
     */
    private $meetingListItemJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_info_get_json", nullable=true)
     */
    private $meetingInfoGetJson;

    /** @var MeetingListItem */
    private $meetingListItem;

    /** @var MeetingInfoGet */
    private $meetingInfoGet;

    /**
     * @var RegistrantEntity[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="RegistrantEntity",
     *     mappedBy="meeting",
     *     cascade={"persist", "remove"}
     * )
     */
    private $registrants;

    /**
     * @var RecordingEntity[]|ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="RecordingEntity",
     *     mappedBy="meeting",
     * )
     */
    private $recordings;

    public function __construct()
    {
        $this->registrants = new ArrayCollection();
        $this->recordings = new ArrayCollection();
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
     * @return RegistrantEntity[]|ArrayCollection
     */
    public function getRegistrants()
    {
        return $this->registrants;
    }

    /**
     * @return RecordingEntity[]|ArrayCollection
     */
    public function getRecordings()
    {
        return $this->recordings;
    }

    /**
     * @ORM\PostLoad
     *
     * @throws Exception
     */
    public function postLoad()
    {
        if (!is_null($this->meetingListItemJson)) {
            $this->meetingListItem = MeetingListItem::fromJson($this->meetingListItemJson);
        }
        if (!is_null($this->meetingInfoGetJson)) {
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
        if (!is_null($this->meetingListItem)) {
            $this->meetingListItemJson = json_encode($this->meetingListItem);
        }
        if (!is_null($this->meetingInfoGet)) {
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
     * @param MeetingListItem $meetingListItem
     *
     * @throws Exception
     *
     * @return MeetingEntity
     */
    public function setMeetingListItem($meetingListItem)
    {
        if (is_null($this->id)) {
            $this->id = $meetingListItem->id;
        } elseif ($this->id != $meetingListItem->id) {
            throw new Exception('the MeetingEntity identifier differs from the MeetingListItem identifier');
        }
        $this->meetingListItem = $meetingListItem;

        return $this;
    }

    /**
     * @param MeetingInfoGet $meetingInfoGet
     *
     * @throws Exception
     *
     * @return MeetingEntity
     */
    public function setMeetingInfoGet($meetingInfoGet)
    {
        if (null === $this->id) {
            $this->id = $meetingInfoGet->id;
        } elseif ($this->id != $meetingInfoGet->id) {
            throw new Exception('the MeetingEntity identifier differs from the MeetingInfoGet identifier');
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
        return !is_null($this->course);
    }

    /**
     * @return bool
     */
    public function isUserMeeting()
    {
        return !is_null($this->user) && is_null($this->course);
    }

    /**
     * @return bool
     */
    public function isGlobalMeeting()
    {
        return is_null($this->user) && is_null($this->course);
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
        /** @var User[] $users */
        $users = [];
        if (!$this->isCourseMeeting()) {
            $criteria = ['active' => true];
            $users = Database::getManager()->getRepository('ChamiloUserBundle:User')->findBy($criteria);
        } elseif (is_null($this->session)) {
            if (!is_null($this->course)) {
                /** @var CourseRelUser $courseRelUser */
                foreach ($this->course->getUsers() as $courseRelUser) {
                    $users[] = $courseRelUser->getUser();
                }
            }
        } else {
            if (!is_null($this->course)) {
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

    /**
     * @return bool
     */
    public function requiresRegistration()
    {
        return
            MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE === $this->meetingInfoGet->settings->approval_type;
        /*return
            MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $this->meetingInfoGet->settings->approval_type;*/
    }

    /**
     * @return bool
     */
    public function hasCloudAutoRecordingEnabled()
    {
        return 'cloud' === $this->meetingInfoGet->settings->auto_recording;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasRegisteredUser($user)
    {
        return $this->getRegistrants()->exists(
            function (RegistrantEntity $registrantEntity) use (&$user) {
                return $registrantEntity->getUser() === $user;
            }
        );
    }

    /**
     * @param User $user
     *
     * @return RegistrantEntity|null
     */
    public function getRegistrant($user)
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
     *
     * @return string
     */
    public function getIntroduction()
    {
        $introduction = sprintf('<h1>%s</h1>', $this->meetingInfoGet->topic);
        if (!$this->isGlobalMeeting()) {
            $introduction .= sprintf('<p>%s (%s)</p>', $this->formattedStartTime, $this->formattedDuration);
        }
        if ($this->user) {
            $introduction .= sprintf('<p>%s</p>', $this->user->getFullname());
        } elseif ($this->isCourseMeeting()) {
            if (is_null($this->session)) {
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
    private function initializeDisplayableProperties()
    {
        $zoomPlugin = new \ZoomPlugin();
        $this->typeName = [
            API\Meeting::TYPE_INSTANT => $zoomPlugin->get_lang('InstantMeeting'),
            API\Meeting::TYPE_SCHEDULED => $zoomPlugin->get_lang('ScheduledMeeting'),
            API\Meeting::TYPE_RECURRING_WITH_NO_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithNoFixedTime'),
            API\Meeting::TYPE_RECURRING_WITH_FIXED_TIME => $zoomPlugin->get_lang('RecurringWithFixedTime'),
        ][$this->meetingInfoGet->type];
        if (property_exists($this, 'status')) {
            $this->statusName = [
                'waiting' => $zoomPlugin->get_lang('Waiting'),
                'started' => $zoomPlugin->get_lang('Started'),
                'finished' => $zoomPlugin->get_lang('Finished'),
            ][$this->meetingInfoGet->status];
        }
        $this->startDateTime = null;
        $this->formattedStartTime = '';
        $this->durationInterval = null;
        $this->formattedDuration = '';
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
