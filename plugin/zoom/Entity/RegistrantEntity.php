<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\PluginBundle\Zoom\API\CreatedRegistration;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrant;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrantListItem;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Class RegistrantEntity.
 *
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Zoom\RegistrantEntityRepository")
 * @ORM\Table(
 *     name="plugin_zoom_registrant",
 *     indexes={
 *         @ORM\Index(name="user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="meeting_id_index", columns={"meeting_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class RegistrantEntity
{
    /** @var string */
    public $fullName;
    /**
     * @var string
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\UserBundle\Entity\User",
     * )
     * @ORM\JoinColumn(name="user_id", nullable=false)
     */
    private $user;

    /**
     * @var MeetingEntity
     * @ORM\ManyToOne(
     *     targetEntity="MeetingEntity",
     *     inversedBy="registrants",
     * )
     * @ORM\JoinColumn(name="meeting_id")
     */
    private $meeting;

    /**
     * @var string
     * @ORM\Column(type="text", name="created_registration_json", nullable=true)
     */
    private $createdRegistrationJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_registrant_list_item_json", nullable=true)
     */
    private $meetingRegistrantListItemJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_registrant_json", nullable=true)
     */
    private $meetingRegistrantJson;

    /** @var CreatedRegistration */
    private $createdRegistration;

    /** @var MeetingRegistrant */
    private $meetingRegistrant;

    /** @var MeetingRegistrantListItem */
    private $meetingRegistrantListItem;

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Registrant %d', $this->id);
    }

    /**
     * @return MeetingEntity
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @throws Exception
     *
     * @return MeetingRegistrantListItem
     */
    public function getMeetingRegistrantListItem()
    {
        return $this->meetingRegistrantListItem;
    }

    /**
     * @throws Exception
     *
     * @return CreatedRegistration
     */
    public function getCreatedRegistration()
    {
        return $this->createdRegistration;
    }

    /**
     * @throws Exception
     *
     * @return MeetingRegistrant
     */
    public function getMeetingRegistrant()
    {
        return $this->meetingRegistrant;
    }

    /**
     * @param MeetingEntity $meeting
     *
     * @return $this
     */
    public function setMeeting($meeting)
    {
        $this->meeting = $meeting;
        $this->meeting->getRegistrants()->add($this);

        return $this;
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
     * @param MeetingRegistrantListItem $meetingRegistrantListItem
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setMeetingRegistrantListItem($meetingRegistrantListItem)
    {
        if (!is_null($this->meeting) && $this->meeting->getId() != $meetingRegistrantListItem->id) {
            throw new Exception('RegistrantEntity meeting id differs from MeetingRegistrantListItem id');
        }
        $this->meetingRegistrantListItem = $meetingRegistrantListItem;
        $this->computeFullName();

        return $this;
    }

    /**
     * @param CreatedRegistration $createdRegistration
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setCreatedRegistration($createdRegistration)
    {
        if (null === $this->id) {
            $this->id = $createdRegistration->registrant_id;
        } elseif ($this->id != $createdRegistration->registrant_id) {
            throw new Exception('RegistrantEntity id differs from CreatedRegistration identifier');
        }
        $this->createdRegistration = $createdRegistration;

        return $this;
    }

    /**
     * @param MeetingRegistrant $meetingRegistrant
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setMeetingRegistrant($meetingRegistrant)
    {
        $this->meetingRegistrant = $meetingRegistrant;
        $this->computeFullName();

        return $this;
    }

    /**
     * @ORM\PostLoad
     *
     * @throws Exception
     */
    public function postLoad()
    {
        if (!is_null($this->meetingRegistrantJson)) {
            $this->meetingRegistrant = MeetingRegistrant::fromJson($this->meetingRegistrantJson);
        }
        if (!is_null($this->createdRegistrationJson)) {
            $this->createdRegistration = CreatedRegistration::fromJson($this->createdRegistrationJson);
        }
        if (!is_null($this->meetingRegistrantListItemJson)) {
            $this->meetingRegistrantListItem = MeetingRegistrantListItem::fromJson(
                $this->meetingRegistrantListItemJson
            );
        }
        $this->computeFullName();
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlush()
    {
        if (!is_null($this->meetingRegistrant)) {
            $this->meetingRegistrantJson = json_encode($this->meetingRegistrant);
        }
        if (!is_null($this->createdRegistration)) {
            $this->createdRegistrationJson = json_encode($this->createdRegistration);
        }
        if (!is_null($this->meetingRegistrantListItem)) {
            $this->meetingRegistrantListItemJson = json_encode($this->meetingRegistrantListItem);
        }
    }

    public function computeFullName()
    {
        $this->fullName = api_get_person_name(
            $this->meetingRegistrant->first_name,
            $this->meetingRegistrant->last_name
        );
    }
}
