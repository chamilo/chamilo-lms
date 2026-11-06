<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Mailer;

use Chamilo\CoreBundle\Service\Mailer\MassMailingTransportSelector;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

final class MassMailingTransportSelectorTest extends TestCase
{
    /**
     * @dataProvider transportConfigurations
     */
    public function testBulkRoutingIsEnabledOnlyForADistinctConfiguredDsn(
        ?string $mainDsn,
        ?string $massMailingDsn,
        string $expectedMainDsn,
        ?string $expectedMassMailingDsn,
    ): void {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')
            ->willReturnCallback(static function (string $name, bool $loadFromDb = false) use ($mainDsn, $massMailingDsn): ?string {
                self::assertFalse($loadFromDb, 'Keep the existing effective setting lookup.');

                return match ($name) {
                    'mail.mailer_dsn' => $mainDsn,
                    'mail.mailer_dsn_massmailing' => $massMailingDsn,
                    default => self::fail('Unexpected mail setting: '.$name),
                };
            })
        ;

        $selector = new MassMailingTransportSelector($settingsManager);
        self::assertSame($expectedMainDsn, $selector->getMainDsn());
        self::assertSame($expectedMassMailingDsn, $selector->getMassMailingDsn());

        $message = new Email();
        $selector->routeMassMailing($message);
        self::assertSame(
            null !== $expectedMassMailingDsn,
            $message->getHeaders()->has('X-Transport'),
        );
        if (null !== $expectedMassMailingDsn) {
            self::assertSame('massmailing', $message->getHeaders()->get('X-Transport')->getBodyAsString());
        }
    }

    public static function transportConfigurations(): array
    {
        return [
            'missing mass mail DSN' => ['smtp://main.example.test', null, 'smtp://main.example.test', null],
            'empty mass mail DSN' => ['smtp://main.example.test', '', 'smtp://main.example.test', null],
            'identical transport DSNs' => ['smtp://main.example.test', 'smtp://main.example.test', 'smtp://main.example.test', null],
            'distinct mass mail DSN' => ['smtp://main.example.test', 'smtp://bulk.example.test', 'smtp://main.example.test', 'smtp://bulk.example.test'],
            'missing main DSN' => [null, '', 'null://null', null],
        ];
    }
}
