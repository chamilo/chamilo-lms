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
 * @ORM\Entity(repositoryClass="RegistrantRepository")
 * @ORM\Table(
 *     name="plugin_zoom_registrant",
 *     indexes={
 *         @ORM\Index(name="user_id_index", columns={"user_id"}),
 *         @ORM\Index(name="meeting_id_index", columns={"meeting_id"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Registrant
{
    /** @var string */
    public $fullName;

    /**
     * @var int
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var Meeting
     * @ORM\ManyToOne(targetEntity="Meeting", inversedBy="registrants")
     * @ORM\JoinColumn(name="meeting_id", referencedColumnName="id")
     */
    protected $meeting;

    /**
     * @var string
     * @ORM\Column(type="text", name="created_registration_json", nullable=true)
     */
    protected $createdRegistrationJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_registrant_list_item_json", nullable=true)
     */
    protected $meetingRegistrantListItemJson;

    /**
     * @var string
     * @ORM\Column(type="text", name="meeting_registrant_json", nullable=true)
     */
    protected $meetingRegistrantJson;

    /**
     * @var Signature|null
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Zoom\Signature", mappedBy="registrant", orphanRemoval=true)
     */
    protected $signature;

    /** @var CreatedRegistration */
    protected $createdRegistration;

    /** @var MeetingRegistrant */
    protected $meetingRegistrant;

    /** @var MeetingRegistrantListItem */
    protected $meetingRegistrantListItem;

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Registrant %d', $this->id);
    }

    /**
     * @return Meeting
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @param Meeting $meeting
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
     * @throws Exception
     *
     * @return MeetingRegistrantListItem
     */
    public function getMeetingRegistrantListItem()
    {
        return $this->meetingRegistrantListItem;
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

    public function computeFullName()
    {
        $this->fullName = api_get_person_name(
            $this->meetingRegistrant->first_name,
            $this->meetingRegistrant->last_name
        );
    }

    public function getJoinUrl()
    {
        if (!$this->createdRegistration) {
            return '';
        }

        return $this->createdRegistration->join_url;
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
     * @throws Exception
     *
     * @return MeetingRegistrant
     */
    public function getMeetingRegistrant()
    {
        return $this->meetingRegistrant;
    }

    /**
     * @throws Exception
     */
    public function setMeetingRegistrant(API\RegistrantSchema $meetingRegistrant): Registrant
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
        if (null !== $this->meetingRegistrantJson) {
            $this->meetingRegistrant = MeetingRegistrant::fromJson($this->meetingRegistrantJson);
        }
        if (null !== $this->createdRegistrationJson) {
            $this->createdRegistration = CreatedRegistration::fromJson($this->createdRegistrationJson);
        }
        if (null !== $this->meetingRegistrantListItemJson) {
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
        if (null !== $this->meetingRegistrant) {
            $this->meetingRegistrantJson = json_encode($this->meetingRegistrant);
        }
        if (null !== $this->createdRegistration) {
            $this->createdRegistrationJson = json_encode($this->createdRegistration);
        }
        if (null !== $this->meetingRegistrantListItem) {
            $this->meetingRegistrantListItemJson = json_encode($this->meetingRegistrantListItem);
        }
    }

    public function setSignature(Signature $signature): void
    {
        $this->signature = $signature;

        $signature->setRegistrant($this);
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }
}
