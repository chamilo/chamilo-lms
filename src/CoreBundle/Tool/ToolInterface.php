<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

/**
 * Interface ToolInterface.
 */
interface ToolInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getLink();

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getCategory();
}
