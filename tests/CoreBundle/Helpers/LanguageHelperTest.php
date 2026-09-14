<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Helpers\LanguageHelper;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The interface only renders right-to-left when LanguageHelper::getTextDirection()
 * correctly identifies an RTL language -- a wrong answer here means the whole app
 * stays LTR (or a menu, chevrons and mirrored text disagree with the surrounding page).
 * See LanguageFixtures::getLanguages() for the 'direction' => 'rtl' source of truth.
 */
class LanguageHelperTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testKnownRtlLanguageReturnsRtl(): void
    {
        self::bootKernel();

        /** @var LanguageHelper $helper */
        $helper = static::getContainer()->get(LanguageHelper::class);

        $this->assertSame('rtl', $helper->getTextDirection('ar'));
        $this->assertSame('rtl', $helper->getTextDirection('he_IL'));
    }

    public function testKnownLtrLanguageReturnsLtr(): void
    {
        self::bootKernel();

        /** @var LanguageHelper $helper */
        $helper = static::getContainer()->get(LanguageHelper::class);

        $this->assertSame('ltr', $helper->getTextDirection('en_US'));
        $this->assertSame('ltr', $helper->getTextDirection('fr_FR'));
    }

    public function testUnresolvableIsocodeFallsBackToLtr(): void
    {
        self::bootKernel();

        /** @var LanguageHelper $helper */
        $helper = static::getContainer()->get(LanguageHelper::class);

        $this->assertSame('ltr', $helper->getTextDirection('not-a-real-isocode'));
    }

    public function testNoRequestAndNoArgumentFallsBackToLtr(): void
    {
        self::bootKernel();

        /** @var LanguageHelper $helper */
        $helper = static::getContainer()->get(LanguageHelper::class);

        // No request has been pushed onto the stack in this CLI test context, so
        // getMainRequest() is null -- must not throw, and must degrade to 'ltr'.
        $this->assertSame('ltr', $helper->getTextDirection());
    }

    /**
     * A Chamilo sub-language (an admin-created variant of an existing language, e.g.
     * "ar_42" derived from "ar") only overrides a handful of translation strings and
     * has no 'direction' entry of its own in LanguageFixtures -- it must inherit its
     * parent's direction, exactly like LanguageHelper::getWcagIso() already resolves
     * a sub-language's WCAG tag to its parent's. Before this helper existed,
     * api_get_text_direction() matched isocodes exactly and got this wrong.
     *
     * Note: LanguageListener::postPersist() overwrites whatever isocode is set on a
     * language that has a parent, generating "<parent_base>_<id>" instead (see
     * Language::generateIsoCodeForChild()) -- so the isocode to resolve against is only
     * known after flush(), not the one set on the entity before persisting it.
     */
    public function testSubLanguageInheritsParentDirection(): void
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        /** @var LanguageRepository $languageRepository */
        $languageRepository = static::getContainer()->get(LanguageRepository::class);
        $parent = $languageRepository->findByIsoCode('ar');
        $this->assertNotNull($parent, 'LanguageFixtures must ship an "ar" language for this test to be meaningful.');

        $subLanguage = (new Language())
            ->setOriginalName('Arabic (sub)')
            ->setEnglishName('arabic_sub_test')
            ->setIsocode('ar_sub42')
            ->setAvailable(true)
            ->setParent($parent)
        ;
        $em->persist($subLanguage);
        $em->flush();

        /** @var LanguageHelper $helper */
        $helper = static::getContainer()->get(LanguageHelper::class);

        $this->assertSame('rtl', $helper->getTextDirection($subLanguage->getIsocode()));
    }

    public function testGetRtlIsocodesMatchesLanguageFixtures(): void
    {
        $isocodes = LanguageHelper::getRtlIsocodes();

        $this->assertContains('ar', $isocodes);
        $this->assertContains('he_IL', $isocodes);
        $this->assertNotContains('en_US', $isocodes);
    }
}
