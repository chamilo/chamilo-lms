<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

interface EntityAccessUrlInterface
{
    public function setUrl(?AccessUrl $url): self;

    public function getUrl(): ?AccessUrl;
}
