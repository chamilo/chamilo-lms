<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\WhispeakAuth;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LogEventLp.
 *
 * @package Chamilo\PluginBundle\Entity\WhispeakAuth
 *
 * @ORM\Entity()
 */
class LogEventLp extends LogEvent
{
    /**
     * @var CLpItem
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpItem")
     * @ORM\JoinColumn(name="lp_item_id", referencedColumnName="iid")
     */
    private $lpItem;
    /**
     * @var CLp
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLp")
     * @ORM\JoinColumn(name="lp_id", referencedColumnName="iid")
     */
    private $lp;

    /**
     * @return CLpItem
     */
    public function getLpItem()
    {
        return $this->lpItem;
    }

    /**
     * @param CLpItem $lpItem
     *
     * @return LogEventLp
     */
    public function setLpItem($lpItem)
    {
        $this->lpItem = $lpItem;

        return $this;
    }

    /**
     * @return CLp
     */
    public function getLp()
    {
        return $this->lp;
    }

    /**
     * @param CLp $lp
     *
     * @return LogEventLp
     */
    public function setLp($lp)
    {
        $this->lp = $lp;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeString()
    {
        $lpName = $this->lp->getName();
        $itemTitle = $this->getLpItem()->getTitle();

        return "$lpName > $itemTitle";
    }
}
