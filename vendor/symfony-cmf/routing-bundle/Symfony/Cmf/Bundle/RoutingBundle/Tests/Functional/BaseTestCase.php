<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\Util\PathHelper;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Component\Testing\Functional\BaseTestCase as ComponentBaseTestCase;
use Symfony\Cmf\Bundle\RoutingBundle\Tests\Resources\Document\Content;

class BaseTestCase extends ComponentBaseTestCase
{
    /**
     * @return DocumentManager
     */
    protected function getDm()
    {
        $dm = $this->db('PHPCR')->getOm();

        return $dm;
    }

    /**
     * @param string $path
     *
     * @return Route
     */
    protected function createRoute($path)
    {
        $parentPath = PathHelper::getParentPath($path);
        $parent = $this->getDm()->find(null, $parentPath);
        $name = PathHelper::getNodeName($path);
        $route = new Route;
        $route->setPosition($parent, $name);
        $this->getDm()->persist($route);
        $this->getDm()->flush();

        return $route;
    }

    /**
     * @param string $path
     *
     * @return Content
     */
    protected function createContent($path = '/test/content')
    {
        $content = new Content();
        $content->setId($path);
        $content->setTitle('Foo Content');
        $this->getDm()->persist($content);
        $this->getDm()->flush();

        return $content;
    }
}
