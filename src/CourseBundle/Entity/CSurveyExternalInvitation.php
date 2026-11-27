<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\ORM\Mapping as ORM;

/** External recipients are deliberately separate from anonymous answers. */
#[ORM\Entity]
#[ORM\Table(name: 'c_survey_external_invitation')]
class CSurveyExternalInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: CSurveyInvitation::class)]
    #[ORM\JoinColumn(name: 'invitation_id', referencedColumnName: 'iid', nullable: false, unique: true, onDelete: 'CASCADE')]
    private CSurveyInvitation $invitation;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', nullable: false, onDelete: 'CASCADE')]
    private AccessUrl $portal;

    #[ORM\Column(length: 254)]
    private string $email;

    public function __construct(CSurveyInvitation $invitation, AccessUrl $portal, string $email)
    {
        $this->invitation = $invitation;
        $this->portal = $portal;
        $this->email = $email;
    }

    public function getInvitation(): CSurveyInvitation
    {
        return $this->invitation;
    }

    public function getPortal(): AccessUrl
    {
        return $this->portal;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
