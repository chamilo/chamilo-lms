<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Class CQuizDestinationResult.
 *
 * @package Chamilo\CourseBundle\Entity
 *
 * ORM\Table(name="c_quiz_destination_result")
 * ORM\Entity()
 */
class CQuizDestinationResult
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var User
     *
     * @ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;
    /**
     * @var TrackEExercises
     *
     * @ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TrackEExercises")
     * @ORM\JoinColumn(name="exe_id", referencedColumnName="exe_id", onDelete="CASCADE")
     */
    private $exe;
    /**
     * @var string
     *
     * @ORM\Column(name="achieved_level", type="string")
     */
    private $achievedLevel;
    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string")
     */
    private $hash;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return CQuizDestinationResult
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return CQuizDestinationResult
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return TrackEExercises
     */
    public function getExe()
    {
        return $this->exe;
    }

    /**
     * @param TrackEExercises $exe
     *
     * @return CQuizDestinationResult
     */
    public function setExe($exe)
    {
        $this->exe = $exe;

        return $this;
    }

    /**
     * @return string
     */
    public function getAchievedLevel()
    {
        return $this->achievedLevel;
    }

    /**
     * @param string $achievedLevel
     *
     * @return CQuizDestinationResult
     */
    public function setAchievedLevel($achievedLevel)
    {
        $this->achievedLevel = $achievedLevel;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return CQuizDestinationResult
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }
}
