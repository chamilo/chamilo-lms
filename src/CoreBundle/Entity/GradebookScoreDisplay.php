<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreDisplay.
 *
 * @ORM\Table(name="gradebook_score_display", indexes={
 *     @ORM\Index(name="category_id", columns={"category_id"})
 * })
 * @ORM\Entity
 */
class GradebookScoreDisplay
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $score;

    /**
     * @ORM\Column(name="display", type="string", length=40, nullable=false)
     */
    protected ?string $display = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $category;

    /**
     * @ORM\Column(name="score_color_percent", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $scoreColorPercent;

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    public function setDisplay(string $display): self
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display.
     *
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    public function setScoreColorPercent(float $scoreColorPercent): self
    {
        $this->scoreColorPercent = $scoreColorPercent;

        return $this;
    }

    /**
     * Get scoreColorPercent.
     *
     * @return float
     */
    public function getScoreColorPercent()
    {
        return $this->scoreColorPercent;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
