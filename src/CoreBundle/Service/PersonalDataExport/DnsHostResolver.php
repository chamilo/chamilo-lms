<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

interface DnsHostResolver
{
    /**
     * @return list<string> IPv4 and/or IPv6 addresses for the host
     */
    public function resolve(string $host): array;
}
