<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\Language;
use PHPUnit\Framework\TestCase;

/**
 * Doctrine hands out a sub-language as a proxy (a subclass of Language) when a relation loaded
 * it first, while its parent may already be a fully loaded Language. Every page renders the text
 * direction of each language through getParent(), so a return type tied to the proxy class broke
 * the whole page with a TypeError (the "My courses" list stayed empty).
 */
final class LanguageTest extends TestCase
{
    public function testASubLanguageLoadedAsASubclassReturnsItsParent(): void
    {
        $parent = (new Language())->setIsocode('fr_FR');
        $subLanguage = new class extends Language {};
        $subLanguage->setIsocode('fr_69')->setParent($parent);

        self::assertSame($parent, $subLanguage->getParent());
    }
}
