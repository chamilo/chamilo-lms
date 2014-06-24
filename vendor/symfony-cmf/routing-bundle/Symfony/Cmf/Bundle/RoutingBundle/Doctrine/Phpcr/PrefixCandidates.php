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

use PHPCR\Util\PathHelper;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Symfony\Cmf\Component\Routing\Candidates\Candidates;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prefix based strategy for storing routes in a tree with several prefixes.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class PrefixCandidates extends Candidates
{
    /**
     * Places in the PHPCR tree where routes are located.
     *
     * @var array
     */
    protected $idPrefixes = array();

    /**
     * @var string
     */
    protected $managerName;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param array           $prefixes The prefixes to use. If one of them is
     *                                  an empty string, the whole repository
     *                                  is used for routing.
     * @param array           $locales  Allowed locales.
     * @param ManagerRegistry $doctrine Used when the URL matches one of the
     *                                  $locales. This must be the same
     *                                  document manager as the RouteProvider
     *                                  is using.
     * @param int             $limit    Limit to candidates generated per prefix.
     */
    public function __construct(array $prefixes, array $locales = array(), ManagerRegistry $doctrine = null, $limit = 20)
    {
        parent::__construct($locales, $limit);
        $this->setPrefixes($prefixes);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     *
     * A name is a candidate if it starts with one of the prefixes
     */
    public function isCandidate($name)
    {
        foreach ($this->getPrefixes() as $prefix) {
            // $name is the route document path
            if (($name === $prefix || 0 === strpos($name, $prefix . '/'))
                && PathHelper::assertValidAbsolutePath($name, false, false)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @param QueryBuilder $queryBuilder
     */
    public function restrictQuery($queryBuilder)
    {
        $prefixes = $this->getPrefixes();
        if (array_search('', $prefixes) || !count($prefixes)) {
            return;
        }

        $where = $queryBuilder->andWhere()->orX();
        foreach ($prefixes as $prefix) {
            $where->descendant($prefix, $queryBuilder->getPrimaryAlias());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCandidates(Request $request)
    {
        $candidates = array();
        $url = $request->getPathInfo();
        foreach ($this->getPrefixes() as $prefix) {
            $candidates = array_unique(array_merge($candidates, $this->getCandidatesFor($url, $prefix)));
        }

        $locale = $this->determineLocale($url);
        if ($locale) {
            $url = substr($url, strlen($locale) + 1);
            foreach ($this->getPrefixes() as $prefix) {
                $candidates = array_unique(array_merge($candidates, $this->getCandidatesFor($url, $prefix)));
            }
        }

        // filter out things like double // or trailing / - this would trigger an exception on the document manager.
        foreach ($candidates as $key => $candidate) {
            if (! PathHelper::assertValidAbsolutePath($candidate, false, false)) {
                unset($candidates[$key]);
            }
        }

        return $candidates;
    }

    /**
     * Set the prefixes handled by this strategy.
     *
     * @param array $prefixes List of prefixes, possibly including ''.
     */
    public function setPrefixes(array $prefixes)
    {
        $this->idPrefixes = $prefixes;
    }

    /**
     * Append a prefix to the allowed prefixes.
     *
     * @param string $prefix A prefix
     */
    public function addPrefix($prefix)
    {
        $this->idPrefixes[] = $prefix;
    }

    /**
     * Get all currently configured prefixes where to look for routes.
     *
     * @return array The prefixes.
     */
    public function getPrefixes()
    {
        return $this->idPrefixes;
    }

    /**
     * Set the doctrine document manager name.
     *
     * @param string $manager
     */
    public function setManagerName($manager)
    {
        $this->managerName = $manager;
    }

    /**
     * {@inheritDoc}
     *
     * The normal phpcr-odm locale listener "waits" until the routing completes
     * as the locale is usually defined inside the route. We need to set it
     * already in case the route document itself is translated.
     *
     * For example the CmfSimpleCmsBundle Page documents.
     */
    protected function determineLocale($url)
    {
        $locale = parent::determineLocale($url);
        if ($locale && $this->doctrine) {
            $this->getDocumentManager()->getLocaleChooserStrategy()->setLocale($locale);
        }

        return $locale;
    }

    /**
     * @return DocumentManager The document manager
     */
    protected function getDocumentManager()
    {
        return $this->doctrine->getManager($this->managerName);
    }

}
