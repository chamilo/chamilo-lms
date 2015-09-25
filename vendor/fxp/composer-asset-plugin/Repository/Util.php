<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;

/**
 * Helper for Repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Util
{
    /**
     * Add repository config.
     *
     * @param IOInterface       $io         The IO instance
     * @param RepositoryManager $rm         The repository mamanger
     * @param array             $repos      The list of already repository added (passed by reference)
     * @param string            $name       The name of the new repository
     * @param array             $repoConfig The config of the new repository
     * @param Pool|null         $pool       The pool
     */
    public static function addRepository(IOInterface $io, RepositoryManager $rm, array &$repos, $name, array $repoConfig, Pool $pool = null)
    {
        $repo = $rm->createRepository($repoConfig['type'], $repoConfig);
        static::addRepositoryInstance($io, $rm, $repos, $name, $repo, $pool);
    }

    /**
     * Add repository instance.
     *
     * @param IOInterface         $io    The IO instance
     * @param RepositoryManager   $rm    The repository mamanger
     * @param array               $repos The list of already repository added (passed by reference)
     * @param string              $name  The name of the new repository
     * @param RepositoryInterface $repo  The repository instance
     * @param Pool|null           $pool  The pool
     */
    public static function addRepositoryInstance(IOInterface $io, RepositoryManager $rm, array &$repos, $name, RepositoryInterface $repo, Pool $pool = null)
    {
        if (!isset($repos[$name])) {
            static::writeAddRepository($io, $name);
            $repos[$name] = $repo;
            $rm->addRepository($repo);

            if (null !== $pool) {
                $pool->addRepository($repo);
            }
        }
    }

    /**
     * Cleans the package name, removing the Composer prefix if present.
     *
     * @param string $name
     *
     * @return string
     */
    public static function cleanPackageName($name)
    {
        if (preg_match('/^[a-z]+\-asset\//', $name, $matches)) {
            $name = substr($name, strlen($matches[0]));
        }

        return $name;
    }

    /**
     * Converts the alias of asset package name by the real asset package name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function convertAliasName($name)
    {
        if (preg_match('/([\w0-9\/_-]+)-\d+(.\d+)?.[\dxX]+$/', $name, $matches)) {
            return $matches[1];
        }

        return $name;
    }

    /**
     * Write the vcs repository name in output console.
     *
     * @param IOInterface $io   The IO instance
     * @param string      $name The vcs repository name
     */
    protected static function writeAddRepository(IOInterface $io, $name)
    {
        if ($io->isVerbose()) {
            $io->write('Adding VCS repository <comment>'.$name.'</comment>');
        }
    }
}
