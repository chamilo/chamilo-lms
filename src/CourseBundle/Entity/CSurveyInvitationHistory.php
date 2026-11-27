<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/** A dispatch batch, independent of the invitation's current reminder date. */
#[ORM\Entity]
#[ORM\Table(name: 'c_survey_invitation_history')]
#[ORM\Index(name: 'idx_survey_history_context', columns: ['survey_id', 'c_id', 'session_id', 'iid'])]
class CSurveyInvitationHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'iid', type: 'integer')]
    private ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CSurvey::class)]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private CSurvey $survey;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Session $session;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable')]
    private DateTimeImmutable $sentAt;

    #[ORM\Column(name: 'email_title', type: 'text')]
    private string $emailTitle;

    #[ORM\Column(name: 'email_body', type: 'text')]
    private string $emailBody;

    #[ORM\Column(name: 'recipient_count', type: 'integer')]
    private int $recipientCount = 0;

    #[ORM\Column(name: 'unanswered_only', type: 'boolean')]
    private bool $unansweredOnly;

    #[ORM\Column(name: 'hide_link', type: 'boolean')]
    private bool $hideLink;

    /**
     * @var Collection<int, CSurveyInvitation>
     */
    #[ORM\ManyToMany(targetEntity: CSurveyInvitation::class)]
    #[ORM\JoinTable(name: 'c_survey_invitation_history_rel_invitation')]
    #[ORM\JoinColumn(name: 'history_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'invitation_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    private Collection $invitations;

    public function __construct(CSurvey $survey, Course $course, ?Session $session, string $emailTitle, string $emailBody, bool $unansweredOnly, bool $hideLink)
    {
        $this->survey = $survey;
        $this->course = $course;
        $this->session = $session;
        $this->emailTitle = $emailTitle;
        $this->emailBody = $emailBody;
        $this->unansweredOnly = $unansweredOnly;
        $this->hideLink = $hideLink;
        $this->sentAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->invitations = new ArrayCollection();
    }

    public function addInvitation(CSurveyInvitation $invitation): void
    {
        if ($invitation->getSurvey() !== $this->survey || $invitation->getCourse() !== $this->course || $invitation->getSession() !== $this->session) {
            throw new InvalidArgumentException('The invitation must belong to the dispatch context.');
        }

        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            ++$this->recipientCount;
        }
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getEmailTitle(): string
    {
        return $this->emailTitle;
    }

    public function getEmailBody(): string
    {
        return $this->emailBody;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function isHideLink(): bool
    {
        return $this->hideLink;
    }
}
