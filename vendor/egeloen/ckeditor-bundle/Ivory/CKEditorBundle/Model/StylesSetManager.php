<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Model;

use Ivory\CKEditorBundle\Exception\StylesSetManagerException;

/**
 * {@inheritdoc}
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class StylesSetManager implements StylesSetManagerInterface
{
    /** @var array */
    protected $stylesSets = array();

    /**
     * Creates a styles set manager.
     *
     * @param array $stylesSets The styles sets.
     */
    public function __construct(array $stylesSets = array())
    {
        $this->setStylesSets($stylesSets);
    }

    /**
     * {@inheritdoc}
     */
    public function hasStylesSets()
    {
        return !empty($this->stylesSets);
    }

    /**
     * {@inheritdoc}
     */
    public function getStylesSets()
    {
        return $this->stylesSets;
    }

    /**
     * {@inheritdoc}
     */
    public function setStylesSets(array $stylesSets)
    {
        foreach ($stylesSets as $name => $styleSet) {
            $this->setStylesSet($name, $styleSet);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasStylesSet($name)
    {
        return isset($this->stylesSets[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getStylesSet($name)
    {
        if (!$this->hasStylesSet($name)) {
            throw StylesSetManagerException::stylesSetDoesNotExist($name);
        }

        return $this->stylesSets[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function setStylesSet($name, array $stylesSet)
    {
        $this->stylesSets[$name] = $stylesSet;
    }
}
