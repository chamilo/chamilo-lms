<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr;

use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\DoctrineProvider;

/**
 * Implement ContentRepositoryInterface for PHPCR-ODM
 *
 * This is <strong>NOT</strong> not a doctrine repository but just the content
 * provider for the NestedMatcher. (you could of course implement this
 * interface in a repository class, if you need that)
 *
 * @author Uwe Jäger
 */
class ContentRepository extends DoctrineProvider implements ContentRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById($id)
    {
        return $this->getObjectManager()->find(null, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function getContentId($content)
    {
        if (! is_object($content)) {
            return null;
        }
        try {
            return $this->getObjectManager()->getUnitOfWork()->getDocumentId($content);
        } catch (\Exception $e) {
            return null;
        }
    }
}
