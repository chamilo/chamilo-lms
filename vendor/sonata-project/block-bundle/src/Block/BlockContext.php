<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Model\BlockInterface;

class BlockContext implements BlockContextInterface
{
    /**
     * @var BlockInterface
     */
    protected $block;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param BlockInterface $block
     * @param array          $settings
     */
    public function __construct(BlockInterface $block, array $settings = [])
    {
        $this->block = $block;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetting($name)
    {
        if (!array_key_exists($name, $this->settings)) {
            throw new \RuntimeException(sprintf('Unable to find the option `%s` (%s) - define the option in the related BlockServiceInterface', $name, $this->block->getType()));
        }

        return $this->settings[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setSetting($name, $value)
    {
        if (!array_key_exists($name, $this->settings)) {
            throw new \RuntimeException(sprintf('It\'s not possible add non existing setting `%s`.', $name));
        }

        $this->settings[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->getSetting('template');
    }
}
