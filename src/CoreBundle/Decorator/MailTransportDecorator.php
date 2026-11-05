<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Decorator;

use Chamilo\CoreBundle\Service\Mailer\MassMailingTransportSelector;
use Exception;
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
        private readonly MassMailingTransportSelector $massMailingTransportSelector,
    ) {}

    public function fromStrings(#[SensitiveParameter] array $dsns): Transports
    {
        $transports = [
            'main' => $this->inner->fromString($this->massMailingTransportSelector->getMainDsn()),
        ];

        $massMailingDsn = $this->massMailingTransportSelector->getMassMailingDsn();
        if (null !== $massMailingDsn) {
            try {
                $transports['massmailing'] = $this->inner->fromString($massMailingDsn);
            } catch (Exception) {
                // An invalid optional DSN must not interrupt transactional mail.
                // Bulk messages still fail on their explicit missing transport; never fall back to main.
                error_log('MailTransportDecorator: the optional mass mailing transport could not be configured.');
            }
        }

        return new Transports($transports);
    }
}
