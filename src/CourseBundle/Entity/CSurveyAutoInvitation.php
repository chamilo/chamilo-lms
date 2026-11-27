<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

/** Explicit opt-in for new subscriptions in one course/session/portal context. */
#[ORM\Entity]
#[ORM\Table(name: 'c_survey_auto_invitation')]
class CSurveyAutoInvitation
{
    #[ORM\Id]
    #[ORM\Column(name: 'context_key', length: 100)]
    private string $contextKey;

    #[ORM\ManyToOne(targetEntity: CSurvey::class)]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    public CSurvey $survey;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', nullable: false, onDelete: 'CASCADE')]
    public Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', nullable: true, onDelete: 'CASCADE')]
    public ?Session $session;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', nullable: false, onDelete: 'CASCADE')]
    public AccessUrl $portal;

    #[ORM\Column(name: 'after_subscription_id', type: 'integer')]
    public int $afterSubscriptionId;

    #[ORM\Column(type: 'text')]
    public string $subject;

    #[ORM\Column(type: 'text')]
    public string $body;

    public function __construct(CSurvey $survey, Course $course, ?Session $session, AccessUrl $portal, int $afterSubscriptionId, string $subject, string $body)
    {
        $this->contextKey = self::key($survey, $course, $session, $portal);
        $this->survey = $survey;
        $this->course = $course;
        $this->session = $session;
        $this->portal = $portal;
        $this->afterSubscriptionId = $afterSubscriptionId;
        $this->subject = $subject;
        $this->body = $body;
    }

    public static function key(CSurvey $survey, Course $course, ?Session $session, AccessUrl $portal): string
    {
        return implode(':', [$survey->getIid(), $course->getId(), $session?->getId() ?? 0, $portal->getId()]);
    }
}
