<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Presenter extends Registrant
{
    public function __toString()
    {
        return sprintf('Presenter %d', $this->id);
    }

    /**
     * @ORM\PostLoad()
     *
     * @throws Exception
     */
    public function postLoad()
    {
        parent::postLoad();
    }

    /**
     * @ORM\PreFlush()
     */
    public function preFlush()
    {
        parent::preFlush();
    }
}
