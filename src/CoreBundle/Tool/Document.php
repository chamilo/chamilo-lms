<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

/**
 * Class Document.
 */
class Document extends AbstractTool
{
    public function getLink()
    {
        return $this->link.':nodeId/';
    }
}
