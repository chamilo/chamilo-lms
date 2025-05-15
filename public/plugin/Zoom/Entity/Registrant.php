<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\PluginBundle\Zoom\API\CreatedRegistration;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrant;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrantListItem;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use phpDocumentor\Reflection\Types\Array_;
use Stringable;

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
#[ORM\Entity(repositoryClass: RegistrantRepository::class)]
#[ORM\Table(name: 'plugin_zoom_registrant')]
#[ORM\Index(columns: ['user_id'], name: 'user_id_index')]
#[ORM\Index(columns: ['meeting_id'], name: 'meeting_id_index')]
class Registrant implements Stringable
{
    public string $fullName;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: Meeting::class, inversedBy: 'registrants')]
    #[ORM\JoinColumn(name: 'meeting_id', referencedColumnName: 'id')]
    protected ?Meeting $meeting;

    #[ORM\Column(name: 'created_registration_json', type: 'text', nullable: true)]
    protected ?string $createdRegistrationJson;

    #[ORM\Column(name: 'meeting_registrant_list_item_json', type: 'text', nullable: true)]
    protected ?string $meetingRegistrantListItemJson;

    #[ORM\Column(name: 'meeting_registrant_json', type: 'text', nullable: true)]
    protected ?string $meetingRegistrantJson;

    protected ?CreatedRegistration $createdRegistration;

    protected ?MeetingRegistrant $meetingRegistrant;

    protected ?MeetingRegistrantListItem $meetingRegistrantListItem;

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Registrant %d', $this->id);
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): static
    {
        $this->meeting = $meeting;
        $this->meeting->getRegistrants()->add($this);

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMeetingRegistrantListItem(): ?MeetingRegistrantListItem
    {
        return $this->meetingRegistrantListItem;
    }

    /**
     * @throws Exception
     */
    public function setMeetingRegistrantListItem(MeetingRegistrantListItem $meetingRegistrantListItem): static
    {
        if (!is_null($this->meeting) && $this->meeting->getId() != $meetingRegistrantListItem->id) {
            throw new Exception('RegistrantEntity meeting id differs from MeetingRegistrantListItem id');
        }
        $this->meetingRegistrantListItem = $meetingRegistrantListItem;
        $this->computeFullName();

        return $this;
    }

    public function computeFullName(): void
    {
        $this->fullName = api_get_person_name(
            $this->meetingRegistrant->first_name,
            $this->meetingRegistrant->last_name
        );
    }

    public function getJoinUrl(): string
    {
        if (!$this->createdRegistration) {
            return '';
        }

        return $this->createdRegistration->join_url;
    }

    /**
     * @throws Exception
     */
    public function getCreatedRegistration(): ?CreatedRegistration
    {
        return $this->createdRegistration;
    }

    /**
     * @throws Exception
     */
    public function setCreatedRegistration(CreatedRegistration $createdRegistration): static
    {
        if (null === $this->id) {
            $this->id = $createdRegistration->registrant_id;
        } elseif ($this->id != $createdRegistration->registrant_id) {
            throw new Exception('RegistrantEntity id differs from CreatedRegistration identifier');
        }
        $this->createdRegistration = $createdRegistration;

        return $this;
    }

    public function getMeetingRegistrant(): ?MeetingRegistrant
    {
        return $this->meetingRegistrant;
    }

    public function setMeetingRegistrant(MeetingRegistrant $meetingRegistrant): static
    {
        $this->meetingRegistrant = $meetingRegistrant;
        $this->computeFullName();

        return $this;
    }

    /**
     * @throws Exception
     */
    #[ORM\PostLoad]
    public function postLoad(): void
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

    #[ORM\PreFlush]
    public function preFlush(): void
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
}
