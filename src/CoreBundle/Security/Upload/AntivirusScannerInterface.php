<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Upload;

interface AntivirusScannerInterface
{
    /** @param resource|false $stream
     * @return array{status: string, threat: ?string, sha256: ?string, size: int}
     */
    public function scan($stream): array;
}
