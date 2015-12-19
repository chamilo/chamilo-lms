<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs;

/**
 * Mock vcs driver for packages test.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockVcsDriverWithPackages extends MockVcsDriver
{
    protected $composer = array(
        'branch:master' => array(
            'name' => 'foobar',
            'version' => '2.0',
        ),
        'branch:1.x' => array(
            'name' => 'foobar',
            'version' => '1.1',
        ),
        'tag:v1.0.0' => array(
            'name' => 'foobar',
            'version' => '1.0',
        ),
        'tag:v1.0.1' => array(
            'name' => 'foobar',
        ),
        'tag:invalid' => array(
            'name' => 'foobar',
            'description' => 'invalid tag name',
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function getRootIdentifier()
    {
        return 'master';
    }

    /**
     * {@inheritdoc}
     */
    public function hasComposerFile($identifier)
    {
        return isset($this->composer['branch:'.$identifier])
            || isset($this->composer['tag:'.$identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function getComposerInformation($identifier)
    {
        if ($this->hasComposerFile($identifier)) {
            if (isset($this->composer['branch:'.$identifier])) {
                return $this->composer['branch:'.$identifier];
            } elseif (isset($this->composer['tag:'.$identifier])) {
                return $this->composer['tag:'.$identifier];
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getBranches()
    {
        return $this->getDataPackages('branch');
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return $this->getDataPackages('tag');
    }

    /**
     * @param string $type
     *
     * @return array
     */
    protected function getDataPackages($type)
    {
        $packages = array();

        foreach ($this->composer as $name => $data) {
            if (0 === strpos($name, $type.':')) {
                $name = substr($name, strpos($name, ':') + 1);
                $packages[$name] = $data;
            }
        }

        return $packages;
    }
}
