<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Resources\DataFixtures\Phpcr;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PHPCR\Util\NodeHelper;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\RedirectRoute;
use Doctrine\ODM\PHPCR\Document\Generic;

class LoadRouteData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        NodeHelper::createPath($manager->getPhpcrSession(), '/test');

        $root = $manager->find(null, '/test');
        $parent = new Generic;
        $parent->setParent($root);
        $parent->setNodename('routing');
        $manager->persist($parent);

        $route = new Route;
        $route->setParentDocument($parent);
        $route->setName('route-1');
        $manager->persist($route);

        $redirectRoute = new RedirectRoute;
        $redirectRoute->setParentDocument($parent);
        $redirectRoute->setName('redirect-route-1');
        $manager->persist($redirectRoute);

        $manager->flush();
    }
}
