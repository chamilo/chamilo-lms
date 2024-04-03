<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Component\Utils\CreateDefaultPages;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageFixtures extends Fixture implements ContainerAwareInterface
{
    public function __construct(
        private CreateDefaultPages $createDefaultPages,
        private TranslatorInterface $translator,
    ) {}

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->createDefaultPages = $container->get(CreateDefaultPages::class);
        $this->translator = $container->get('translator');
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(AccessUserFixtures::ADMIN_USER_REFERENCE);
        $url = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE);

        $locale = $this->translator->getLocale();
        $this->createDefaultPages->createDefaultPages($admin, $url, $locale);
    }
}
