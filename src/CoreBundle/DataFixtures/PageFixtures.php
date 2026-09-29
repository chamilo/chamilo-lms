<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PageHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageFixtures extends Fixture
{
    public function __construct(
        private readonly PageHelper $createDefaultPages,
        private readonly TranslatorInterface $translator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(AccessUserFixtures::ADMIN_USER_REFERENCE, User::class);
        $url = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE, AccessUrl::class);

        $locale = $this->translator->getLocale();
        $this->createDefaultPages->createDefaultPages($admin, $url, $locale);
    }
}
