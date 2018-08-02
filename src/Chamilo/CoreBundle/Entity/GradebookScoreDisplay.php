<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreDisplay.
 *
 * @ORM\Table(name="gradebook_score_display", indexes={@ORM\Index(name="category_id", columns={"category_id"})})
 * @ORM\Entity
 */
class GradebookScoreDisplay
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected $score;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=40, nullable=false)
     */
    protected $display;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    protected $categoryId;

    /**
     * @var float
     *
     * @ORM\Column(name="score_color_percent", type="float", precision=10, scale=0, nullable=false)
     */
    protected $scoreColorPercent;

    /**
     * Set score.
     *
     * @param float $score
     *
     * @return GradebookScoreDisplay
     */
    public function setScore($score)
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

    /**
     * Set display.
     *
     * @param string $display
     *
     * @return GradebookScoreDisplay
     */
    public function setDisplay($display)
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

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return GradebookScoreDisplay
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set scoreColorPercent.
     *
     * @param float $scoreColorPercent
     *
     * @return GradebookScoreDisplay
     */
    public function setScoreColorPercent($scoreColorPercent)
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
