<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Document extends AbstractTool
{
    public function getLink(): string
    {
        return $this->link.':nodeId/';
    }
}
