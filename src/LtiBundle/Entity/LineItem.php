<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\LtiBundle\Entity;

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'lti_lineitem')]
#[ORM\Entity]
class LineItem
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[ORM\ManyToOne(targetEntity: ExternalTool::class, inversedBy: 'lineItems')]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ExternalTool $tool;
    #[ORM\OneToOne(targetEntity: GradebookEvaluation::class)]
    #[ORM\JoinColumn(name: 'evaluation', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GradebookEvaluation $evaluation;
    #[ORM\Column(name: 'resource_id', type: 'string', nullable: true)]
    private string $resourceId;
    #[ORM\Column(name: 'tag', type: 'string', nullable: true)]
    private string $tag;
    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    private DateTime $startDate;
    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    private DateTime $endDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTool(): ExternalTool
    {
        return $this->tool;
    }

    public function setTool(ExternalTool $tool): static
    {
        $this->tool = $tool;

        return $this;
    }

    public function getEvaluation(): GradebookEvaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(GradebookEvaluation $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): static
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function toArray(): array
    {
        $baseTool = $this->tool->getParent() ?: $this->tool;

        $data = [
            'scoreMaximum' => $this->evaluation->getMax(),
            'label' => $this->evaluation->getName(),
            'tag' => $this->tag,
            'resourceLinkId' => (string) $baseTool->getId(),
            'resourceId' => $this->resourceId,
        ];

        if ($this->startDate) {
            $data['startDateTime'] = $this->startDate->format(DateTimeInterface::ATOM);
        }

        if ($this->endDate) {
            $data['endDateTime'] = $this->endDate->format(DateTimeInterface::ATOM);
        }

        return $data;
    }
}
