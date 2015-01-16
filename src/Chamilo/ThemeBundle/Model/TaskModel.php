<?php
/**
 * TaskModel.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Model;


/**
 * Class TaskModel
 *
 * @package Chamilo\ThemeBundle\Model
 */
class TaskModel implements TaskInterface
{

    /**
     *
     */
    const COLOR_AQUA   = 'aqua';
    /**
     *
     */
    const COLOR_GREEN  = 'green';
    /**
     *
     */
    const COLOR_RED    = 'red';
    /**
     *
     */
    const COLOR_YELLOW = 'yellow';


    /**
     * @var int
     */
    protected $progress;

    /**
     * @var string
     */
    protected $color = TaskModel::COLOR_AQUA;

    /**
     * @var null
     */
    protected $title;

    /**
     * @param null   $title
     * @param int    $progress
     * @param string $color
     */
    function __construct($title = null, $progress = 0, $color = TaskModel::COLOR_AQUA)
    {
        $this->color    = $color;
        $this->progress = $progress;
        $this->title    = $title;
    }


    /**
     * @param string $color
     *
     * @return $this
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $progress
     *
     * @return $this
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param mixed $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getIdentifier() {
        return $this->title;
    }


}
