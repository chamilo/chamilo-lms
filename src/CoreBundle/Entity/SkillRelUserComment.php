<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SkillRelUserComment class.
 *
 * @ORM\Table(
 *     name="skill_rel_user_comment",
 *     indexes={
 *         @ORM\Index(name="idx_select_su_giver", columns={"skill_rel_user_id", "feedback_giver_id"})
 *     }
 * )
 * @ORM\Entity
 */
class SkillRelUserComment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", inversedBy="comments")
     * @ORM\JoinColumn(name="skill_rel_user_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\SkillRelUser $skillRelUser = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="commentedUserSkills")
     * @ORM\JoinColumn(name="feedback_giver_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\User $feedbackGiver = null;

    /**
     * @ORM\Column(name="feedback_text", type="text")
     */
    protected string $feedbackText;

    /**
     * @ORM\Column(name="feedback_value", type="integer", nullable=true, options={"default":1})
     */
    protected int $feedbackValue;

    /**
     * @ORM\Column(name="feedback_datetime", type="datetime", nullable=false)
     */
    protected DateTime $feedbackDateTime;

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
     * Get skillRelUser.
     *
     * @return SkillRelUser
     */
    public function getSkillRelUser()
    {
        return $this->skillRelUser;
    }

    /**
     * Get feedbackGiver.
     *
     * @return User
     */
    public function getFeedbackGiver()
    {
        return $this->feedbackGiver;
    }

    /**
     * Get feedbackText.
     *
     * @return string
     */
    public function getFeedbackText()
    {
        return $this->feedbackText;
    }

    /**
     * Get feedbackValue.
     *
     * @return int
     */
    public function getFeedbackValue()
    {
        return $this->feedbackValue;
    }

    /**
     * Get feedbackDateTime.
     *
     * @return DateTime
     */
    public function getFeedbackDateTime()
    {
        return $this->feedbackDateTime;
    }

    /**
     * Set skillRelUser.
     *
     * @return SkillRelUserComment
     */
    public function setSkillRelUser(SkillRelUser $skillRelUser)
    {
        $this->skillRelUser = $skillRelUser;

        return $this;
    }

    /**
     * Set feedbackGiver.
     *
     * @return SkillRelUserComment
     */
    public function setFeedbackGiver(User $feedbackGiver)
    {
        $this->feedbackGiver = $feedbackGiver;

        return $this;
    }

    /**
     * Set feedbackText.
     *
     * @return SkillRelUserComment
     */
    public function setFeedbackText(string $feedbackText)
    {
        $this->feedbackText = $feedbackText;

        return $this;
    }

    /**
     * Set feebackValue.
     *
     * @return SkillRelUserComment
     */
    public function setFeedbackValue(int $feedbackValue)
    {
        $this->feedbackValue = $feedbackValue;

        return $this;
    }

    /**
     * Set feedbackDateTime.
     *
     * @return SkillRelUserComment
     */
    public function setFeedbackDateTime(DateTime $feedbackDateTime)
    {
        $this->feedbackDateTime = $feedbackDateTime;

        return $this;
    }
}
