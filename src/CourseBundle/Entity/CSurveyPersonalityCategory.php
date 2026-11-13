<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/** A scoring dimension owned by one personality survey. */
#[ORM\Entity]
#[ORM\Table(name: 'c_survey_personality_category')]
#[ORM\Index(name: 'idx_personality_category_survey', columns: ['survey_id'])]
#[ORM\UniqueConstraint(name: 'uniq_personality_survey_label', columns: ['survey_id', 'label'])]
class CSurveyPersonalityCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'iid', type: 'integer')]
    private ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CSurvey::class)]
    #[ORM\JoinColumn(name: 'survey_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private CSurvey $survey;

    #[ORM\Column(name: 'label', type: 'string', length: 120)]
    private string $label;

    #[ORM\Column(name: 'sort', type: 'integer')]
    private int $sort;

    public function __construct(CSurvey $survey, string $label, int $sort)
    {
        $this->survey = $survey;
        $this->label = $label;
        $this->sort = $sort;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }
    public function getSurvey(): CSurvey
    {
        return $this->survey;
    }
    public function getLabel(): string
    {
        return $this->label;
    }
    public function getSort(): int
    {
        return $this->sort;
    }
}
