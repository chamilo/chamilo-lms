<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Decorator;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * The mailer DSN comes from a platform setting, so it must not choose the program the server runs.
 * The sendmail transport keeps its default command; a custom one goes through native:// and php.ini.
 */
#[AsDecorator(decorates: 'mailer.transport_factory.sendmail')]
final readonly class SendmailTransportFactoryDecorator implements TransportFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private TransportFactoryInterface $inner,
    ) {}

    public function create(Dsn $dsn): TransportInterface
    {
        if (null !== $dsn->getOption('command')) {
            throw new InvalidArgumentException('The "command" option of the sendmail mailer DSN is not supported. Use "native://default" with the sendmail_path of php.ini instead.');
        }

        return $this->inner->create($dsn);
    }

    public function supports(Dsn $dsn): bool
    {
        return $this->inner->supports($dsn);
    }
}
