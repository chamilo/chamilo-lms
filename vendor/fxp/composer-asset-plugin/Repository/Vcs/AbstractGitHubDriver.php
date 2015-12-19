<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository\Vcs;

use Composer\Cache;
use Composer\Downloader\TransportException;
use Composer\Json\JsonFile;
use Composer\Repository\Vcs\GitHubDriver as BaseGitHubDriver;

/**
 * Abstract class for GitHub vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractGitHubDriver extends BaseGitHubDriver
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var string|null|false
     */
    protected $redirectApi;

    /**
     * Get the remote content.
     *
     * @param string $url              The URL of content
     * @param bool   $fetchingRepoData Fetching the repo data or not
     *
     * @return mixed The result
     */
    protected function getContents($url, $fetchingRepoData = false)
    {
        $url = $this->getValidContentUrl($url);

        if (null !== $this->redirectApi) {
            return parent::getContents($url, $fetchingRepoData);
        }

        try {
            $contents = $this->getRemoteContents($url);
            $this->redirectApi = false;

            return $contents;
        } catch (TransportException $e) {
            if ($this->hasRedirectUrl($url)) {
                $url = $this->getValidContentUrl($url);
            }

            return parent::getContents($url, $fetchingRepoData);
        }
    }

    /**
     * @param string $url The url
     *
     * @return string The url redirected
     */
    protected function getValidContentUrl($url)
    {
        if (null === $this->redirectApi && false !== $redirectApi = $this->cache->read('redirect-api')) {
            $this->redirectApi = $redirectApi;
        }

        if (is_string($this->redirectApi) && 0 === strpos($url, $this->getRepositoryApiUrl())) {
            $url = $this->redirectApi.substr($url, strlen($this->getRepositoryApiUrl()));
        }

        return $url;
    }

    /**
     * Check if the driver must find the new url.
     *
     * @param string $url The url
     *
     * @return bool
     */
    protected function hasRedirectUrl($url)
    {
        if (null === $this->redirectApi && 0 === strpos($url, $this->getRepositoryApiUrl())) {
            $this->redirectApi = $this->getNewRepositoryUrl();

            if (is_string($this->redirectApi)) {
                $this->cache->write('redirect-api', $this->redirectApi);
            }
        }

        return is_string($this->redirectApi);
    }

    /**
     * Get the new url of repository.
     *
     * @return string|false The new url or false if there is not a new url
     */
    protected function getNewRepositoryUrl()
    {
        try {
            $this->getRemoteContents($this->getRepositoryUrl());
            $headers = $this->remoteFilesystem->getLastHeaders();

            if (!empty($headers[0]) && preg_match('{^HTTP/\S+ (30[1278])}i', $headers[0], $match)) {
                array_shift($headers);

                return $this->findNewLocationInHeader($headers);
            }

            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Find the new url api in the header.
     *
     * @param array $headers The http header
     *
     * @return string|false
     */
    protected function findNewLocationInHeader(array $headers)
    {
        $url = false;

        foreach ($headers as $header) {
            if (0 === strpos($header, 'Location:')) {
                $newUrl = trim(substr($header, 9));
                preg_match('#^(?:(?:https?|git)://([^/]+)/|git@([^:]+):)([^/]+)/(.+?)(?:\.git|/)?$#', $newUrl, $match);
                $owner = $match[3];
                $repository = $match[4];
                $paramPos = strpos($repository, '?');
                $repository = is_int($paramPos) ? substr($match[4], 0, $paramPos) : $repository;
                $url = $this->getRepositoryApiUrl($owner, $repository);
                break;
            }
        }

        return $url;
    }

    /**
     * Get the url API of the repository.
     *
     * @param string $owner
     * @param string $repository
     *
     * @return string
     */
    protected function getRepositoryApiUrl($owner = null, $repository = null)
    {
        $owner = null !== $owner ? $owner : $this->owner;
        $repository = null !== $repository ? $repository : $this->repository;

        return $this->getApiUrl().'/repos/'.$owner.'/'.$repository;
    }

    /**
     * Get the remote content.
     *
     * @param string $url
     *
     * @return bool|string
     */
    protected function getRemoteContents($url)
    {
        return $this->remoteFilesystem->getContents($this->originUrl, $url, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getBranches()
    {
        if ($this->gitDriver) {
            return $this->gitDriver->getBranches();
        }

        if (null === $this->branches) {
            $this->branches = array();
            $resource = $this->getApiUrl().'/repos/'.$this->owner.'/'.$this->repository.'/git/refs/heads?per_page=100';
            $branchBlacklist = 'gh-pages' === $this->getRootIdentifier() ? array() : array('gh-pages');

            $this->doAddBranches($resource, $branchBlacklist);
        }

        return $this->branches;
    }

    /**
     * Push the list of all branch.
     *
     * @param string $resource
     * @param array  $branchBlacklist
     */
    protected function doAddBranches($resource, array $branchBlacklist)
    {
        do {
            $branchData = JsonFile::parseJson((string) $this->getContents($resource), $resource);

            foreach ($branchData as $branch) {
                $name = substr($branch['ref'], 11);

                if (!in_array($name, $branchBlacklist)) {
                    $this->branches[$name] = $branch['object']['sha'];
                }
            }

            $resource = $this->getNextPage();
        } while ($resource);
    }
}
