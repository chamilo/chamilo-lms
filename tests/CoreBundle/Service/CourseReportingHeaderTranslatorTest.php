<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingHeaderTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CourseReportingHeaderTranslatorTest extends TestCase
{
    public function testFallsBackToVueCatalogueWhenSymfonyDoesNotTranslate(): void
    {
        $root = sys_get_temp_dir().'/chamilo-reporting-'.bin2hex(random_bytes(8));
        mkdir($root.'/assets/locales', 0700, true);
        file_put_contents($root.'/assets/locales/fr_FR.json', json_encode(['Last name' => 'Nom', 'First name' => 'Prénom']));

        try {
            $translator = $this->createMock(TranslatorInterface::class);
            $translator->method('trans')->willReturnCallback(static fn (string $label): string => $label);
            $service = new CourseReportingHeaderTranslator($translator, $root);

            self::assertSame('Nom', $service->translate('Last name', 'fr_FR'));
            self::assertSame('Prénom', $service->translate('First name', 'fr-FR'));
            self::assertSame('No translation', $service->translate('No translation', 'fr_FR'));
            self::assertSame('Last name', $service->translate('Last name', '../../fr_FR'));
        } finally {
            unlink($root.'/assets/locales/fr_FR.json');
            rmdir($root.'/assets/locales');
            rmdir($root.'/assets');
            rmdir($root);
        }
    }

    public function testPrefersExistingSymfonyTranslation(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Nom traduit côté serveur');
        $service = new CourseReportingHeaderTranslator($translator, sys_get_temp_dir());

        self::assertSame('Nom traduit côté serveur', $service->translate('Last name', 'fr_FR'));
    }
}
