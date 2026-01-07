<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ColorThemeFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper
    ) {}

    public static function getGroups(): array
    {
        return ['color_theme'];
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(ColorTheme::class)
            ->findOneBy(['slug' => 'chamilo'])
        ;

        if ($existing) {
            return;
        }

        $theme = (new ColorTheme())
            ->setTitle('Chamilo')
            ->setSlug('chamilo')
            ->setVariables([
                '--color-primary-base' => '46 117 163',
                '--color-primary-gradient' => '-1 86 130',
                '--color-primary-button-text' => '46 117 163',
                '--color-primary-button-alternative-text' => '255 255 255',
                '--color-secondary-base' => '243 126 47',
                '--color-secondary-gradient' => '193 81 -31',
                '--color-secondary-button-text' => '255 255 255',
                '--color-tertiary-base' => '51 51 51',
                '--color-tertiary-gradient' => '103 103 103',
                '--color-tertiary-button-text' => '51 51 51',
                '--color-success-base' => '119 170 12',
                '--color-success-gradient' => '80 128 -43',
                '--color-success-button-text' => '255 255 255',
                '--color-info-base' => '13 123 253',
                '--color-info-gradient' => '-33 83 211',
                '--color-info-button-text' => '255 255 255',
                '--color-warning-base' => '245 206 1',
                '--color-warning-gradient' => '189 151 -65',
                '--color-warning-button-text' => '0 0 0',
                '--color-danger-base' => '223 59 59',
                '--color-danger-gradient' => '180 -13 20',
                '--color-danger-button-text' => '255 255 255',
                '--color-form-base' => '46 117 163',
            ])
        ;

        /** @var AccessUrl $accessUrl */
        $accessUrl = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE);

        if (!$accessUrl->getId()) {
            $manager->persist($accessUrl);
            $manager->flush();
        }

        $accessUrlRel = (new AccessUrlRelColorTheme())
            ->setUrl($accessUrl)
            ->setColorTheme($theme)
            ->setActive(true)
        ;

        $theme->addUrl($accessUrlRel);

        $manager->persist($theme);
        $manager->persist($accessUrlRel);
        $manager->flush();
    }
}
