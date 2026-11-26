<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Decorator;

use Chamilo\CoreBundle\Decorator\SendmailTransportFactoryDecorator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\NullTransportFactory;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\SendmailTransportFactory;

/**
 * The mailer DSN is a platform setting edited from the administration, and sub-URL administrators
 * can edit it too: it must not be able to make the server run a program of its choice.
 */
final class SendmailTransportFactoryDecoratorTest extends TestCase
{
    /**
     * @dataProvider dsnsWithCommand
     */
    public function testTheDsnCannotChooseTheSendmailCommand(string $dsn): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createTransportFactory()->fromString($dsn);
    }

    public static function dsnsWithCommand(): iterable
    {
        yield 'sendmail' => ['sendmail://default?command=%2Fusr%2Fbin%2Ftouch%20%2Ftmp%2Fx%20-t'];

        yield 'sendmail over smtp' => ['sendmail+smtp://default?command=%2Fusr%2Fbin%2Ftouch%20%2Ftmp%2Fx%20-bs'];

        yield 'encoded option name' => ['sendmail://default?comm%61nd=%2Fusr%2Fbin%2Ftouch%20%2Ftmp%2Fx%20-t'];

        yield 'nested in failover' => ['failover(null://null sendmail://default?command=%2Fusr%2Fbin%2Ftouch%20%2Ftmp%2Fx%20-t)'];
    }

    /**
     * @dataProvider dsnsWithoutCommand
     */
    public function testTheSendmailTransportKeepsWorkingWithItsDefaultCommand(string $dsn): void
    {
        self::assertInstanceOf(SendmailTransport::class, $this->createTransportFactory()->fromString($dsn));
    }

    public static function dsnsWithoutCommand(): iterable
    {
        yield 'sendmail' => ['sendmail://default'];

        yield 'sendmail over smtp' => ['sendmail+smtp://default'];
    }

    private function createTransportFactory(): Transport
    {
        return new Transport([
            new SendmailTransportFactoryDecorator(new SendmailTransportFactory()),
            new NullTransportFactory(),
        ]);
    }
}
