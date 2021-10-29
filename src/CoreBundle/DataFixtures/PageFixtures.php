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

class PageFixtures extends Fixture implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(AccessUserFixtures::ADMIN_USER_REFERENCE);
        $url = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE);

        /** @var CreateDefaultPages $createDefaultPages */
        $createDefaultPages = $this->container->get(CreateDefaultPages::class);

        $locale = $this->container->get('translator')->getLocale();
        $createDefaultPages->createDefaultPages($admin, $url, $locale);
    }
}
