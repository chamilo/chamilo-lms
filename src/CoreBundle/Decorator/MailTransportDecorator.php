<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Decorator;

use Chamilo\CoreBundle\Settings\SettingsManager;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Transports;

#[AsDecorator(decorates: 'mailer.transport_factory')]
class MailTransportDecorator
{
    public function __construct(
        #[AutowireDecorated]
        private readonly Transport $inner,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function fromStrings(#[SensitiveParameter] array $dsns): Transports
    {
        $dsn = $this->settingsManager->getSetting('mail.mailer_dsn');

        return new Transports([
            $this->inner->fromString($dsn),
        ]);
    }
}
