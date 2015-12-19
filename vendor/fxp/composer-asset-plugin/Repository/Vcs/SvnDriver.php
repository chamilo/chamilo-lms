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

use Composer\Config;
use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Repository\Vcs\SvnDriver as BaseSvnDriver;

/**
 * SVN vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SvnDriver extends BaseSvnDriver
{
    /**
     * {@inheritdoc}
     */
    public function getComposerInformation($identifier)
    {
        $identifier = '/'.trim($identifier, '/').'/';
        $this->infoCache[$identifier] = Util::readCache($this->infoCache, $this->cache, $this->repoConfig['asset-type'], trim($identifier, '/'), true);

        if (!isset($this->infoCache[$identifier])) {
            list($path, $rev) = $this->getPathRev($identifier);
            $resource = $path.$this->repoConfig['filename'];
            $output = $this->getComposerContent($resource, $rev);
            $composer = $this->parseComposerContent($output, $resource, $path, $rev);

            Util::writeCache($this->cache, $this->repoConfig['asset-type'], trim($identifier, '/'), $composer, true);
            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }

    /**
     * Get path and rev.
     *
     * @param string $identifier The identifier
     *
     * @return string[]
     */
    protected function getPathRev($identifier)
    {
        $path = $identifier;
        $rev = '';

        preg_match('{^(.+?)(@\d+)?/$}', $identifier, $match);

        if (!empty($match[2])) {
            $path = $match[1];
            $rev = $match[2];
        }

        return array($path, $rev);
    }

    /**
     * Get the composer content.
     *
     * @param string $resource The resource
     * @param string $rev      The rev
     *
     * @return null|string The composer content
     *
     * @throws TransportException
     */
    protected function getComposerContent($resource, $rev)
    {
        $output = null;

        try {
            $output = $this->execute('svn cat', $this->baseUrl.$resource.$rev);
        } catch (\RuntimeException $e) {
            throw new TransportException($e->getMessage());
        }

        return $output;
    }

    /**
     * Parse the content of composer.
     *
     * @param string|null $output   The output of process executor
     * @param string      $resource The resouce
     * @param string      $path     The path
     * @param string      $rev      The rev
     *
     * @return array The composer
     */
    protected function parseComposerContent($output, $resource, $path, $rev)
    {
        if (!trim($output)) {
            return array('_nonexistent_package' => true);
        }

        $composer = (array) JsonFile::parseJson($output, $this->baseUrl.$resource.$rev);

        return $this->addComposerTime($composer, $path, $rev);
    }

    /**
     * Add time in composer.
     *
     * @param array  $composer The composer
     * @param string $path     The path
     * @param string $rev      The rev
     *
     * @return array The composer
     */
    protected function addComposerTime(array $composer, $path, $rev)
    {
        if (!isset($composer['time'])) {
            $output = $this->execute('svn info', $this->baseUrl.$path.$rev);

            foreach ($this->process->splitLines($output) as $line) {
                if ($line && preg_match('{^Last Changed Date: ([^(]+)}', $line, $match)) {
                    $date = new \DateTime($match[1], new \DateTimeZone('UTC'));
                    $composer['time'] = $date->format('Y-m-d H:i:s');
                    break;
                }
            }
        }

        return $composer;
    }

    /**
     * {@inheritdoc}
     */
    public static function supports(IOInterface $io, Config $config, $url, $deep = false)
    {
        if (0 === strpos($url, 'http') && preg_match('/\/svn|svn\//i', $url)) {
            $url = 'svn'.substr($url, strpos($url, '://'));
        }

        return parent::supports($io, $config, $url, $deep);
    }
}
