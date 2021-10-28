<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
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

        $category = (new PageCategory())
            ->setTitle('home')
            ->setType('grid')
            ->setCreator($admin)
        ;
        $manager->persist($category);

        $locale = $this->container->get('translator')->getLocale();

        $page = (new Page())
            ->setTitle('Welcome')
            ->setContent('Welcome to Chamilo')
            ->setCategory($category)
            ->setCreator($admin)
            ->setLocale($locale)
            ->setEnabled(true)
            ->setUrl($url)
        ;
        $manager->persist($page);

        $indexCategory = (new PageCategory())
            ->setTitle('index')
            ->setType('grid')
            ->setCreator($admin)
        ;
        $manager->persist($indexCategory);

        $indexPage = (new Page())
            ->setTitle('Welcome')
            ->setContent('<img src="/img/document/images/mr_chamilo/svg/teaching.svg" />')
            ->setCategory($indexCategory)
            ->setCreator($admin)
            ->setLocale($locale)
            ->setEnabled(true)
            ->setUrl($url)
        ;
        $manager->persist($indexPage);

        $manager->flush();
    }
}
