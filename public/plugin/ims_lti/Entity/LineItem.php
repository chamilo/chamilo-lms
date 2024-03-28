<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LineItem.
 *
 * @package Chamilo\PluginBundle\Entity\ImsLti
 *
 * @ORM\Table(name="plugin_ims_lti_lineitem")
 * @ORM\Entity()
 */
class LineItem
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;
    /**
     * @var ImsLtiTool
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool", inversedBy="lineItems")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $tool;
    /**
     * @var GradebookEvaluation
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation")
     * @ORM\JoinColumn(name="evaluation", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $evaluation;
    /**
     * @var string
     *
     * @ORM\Column(name="resource_id", type="string", nullable=true)
     */
    private $resourceId;
    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", nullable=true)
     */
    private $tag;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get tool.
     *
     * @return ImsLtiTool
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * Set tool.
     *
     * @param ImsLtiTool $tool
     *
     * @return LineItem
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Get evaluation.
     *
     * @return GradebookEvaluation
     */
    public function getEvaluation()
    {
        return $this->evaluation;
    }

    /**
     * Set evaluation.
     *
     * @param GradebookEvaluation $evaluation
     *
     * @return LineItem
     */
    public function setEvaluation($evaluation)
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    /**
     * Get tag.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set tag.
     *
     * @param string $tag
     *
     * @return LineItem
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return LineItem
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return LineItem
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     *
     * @return LineItem
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $baseTool = $this->tool->getParent() ?: $this->tool;

        $data = [
            'scoreMaximum' => $this->evaluation->getMax(),
            'label' => $this->evaluation->getName(),
            'tag' => (string) $this->tag,
            'resourceLinkId' => (string) $baseTool->getId(),
            'resourceId' => (string) $this->resourceId,
        ];

        if ($this->startDate) {
            $data['startDateTime'] = $this->startDate->format(\DateTime::ATOM);
        }

        if ($this->endDate) {
            $data['endDateTime'] = $this->endDate->format(\DateTime::ATOM);
        }

        return $data;
    }
}
