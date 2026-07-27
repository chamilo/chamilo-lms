<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\MobilePushInstallation;

interface MobilePushProviderInterface
{
    public function isConfigured(): bool;

    public function supports(string $platform): bool;

    public function send(
        MobilePushInstallation $installation,
        int $messageId
    ): MobilePushDelivery;
}
