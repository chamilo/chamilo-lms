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

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\HierarchyInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Model\RedirectRoute as RedirectRouteModel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * {@inheritDoc}
 *
 * This extends the RedirectRoute Model. We need to re-implement everything
 * that the PHPCR Route document adds.
 */
class RedirectRoute extends RedirectRouteModel implements PrefixInterface, HierarchyInterface
{
    /**
     * parent document
     *
     * @var object
     */
    protected $parent;

    /**
     * PHPCR node name
     *
     * @var string
     */
    protected $name;

    /**
     * Child route documents
     *
     * @var Collection
     */
    protected $children;

    /**
     * The part of the PHPCR path that does not belong to the url
     *
     * This field is not persisted in storage.
     *
     * @var string
     */
    protected $idPrefix;

    /**
     * Overwrite to be able to create route without pattern.
     *
     * Additional options:
     *
     * * add_trailing_slash: When set, a trailing slash is appended to the route
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->children = array();
    }

    /**
     * @deprecated Use setParentDocument instead.
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @deprecated Use getParentDocument instead.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Move the route by setting a parent.
     *
     * Note that this will change the URL this route matches.
     *
     * @param object $parent the new parent document
     *
     * @return $this
     */
    public function setParentDocument($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentDocument()
    {
        return $this->parent;
    }

    /**
     * Rename a route by setting its new name.
     *
     * Note that this will change the URL this route matches.
     *
     * @param string $name the new name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Convenience method to set parent and name at the same time.
     *
     * The url will be the url of the parent plus the supplied name.
     */
    public function setPosition($parent, $name)
    {
        $this->parent = $parent;
        $this->name = $name;

        return $this;
    }

    /**
     * PHPCR documents can be moved by setting the id to a new path.
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Overwritten to translate into a move operation.
     */
    public function setStaticPrefix($prefix)
    {
        $this->id = str_replace($this->getStaticPrefix(), $prefix, $this->id);
    }

    public function getPrefix()
    {
        return $this->idPrefix;
    }

    /**
     * {@inheritDoc}
     */
    public function setPrefix($idPrefix)
    {
        $this->idPrefix = $idPrefix;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Overwrite model method as we need to build this
     */
    public function getStaticPrefix()
    {
        return $this->generateStaticPrefix($this->getId(), $this->idPrefix);
    }

    /**
     * @param string $id       PHPCR id of this document
     * @param string $idPrefix part of the id that can be removed
     *
     * @return string the static part of the pattern of this route
     *
     * @throws \LogicException if there is no prefix or the prefix does not match
     */
    public function generateStaticPrefix($id, $idPrefix)
    {
        if (0 == strlen($idPrefix)) {
            throw new \LogicException('Can not determine the prefix. Either this is a new, unpersisted document or the listener that calls setPrefix is not set up correctly.');
        }

        if (strncmp($id, $idPrefix, strlen($idPrefix))) {
            throw new \LogicException("The id prefix '$idPrefix' does not match the route document path '$id'");
        }

        $url = substr($id, strlen($idPrefix));
        if (empty($url)) {
            $url = '/';
        }

        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        $pattern = parent::getPath();
        if ($this->getOption('add_trailing_slash') && '/' !== $pattern[strlen($pattern)-1]) {
            $pattern .= '/';
        };

        return $pattern;
    }

    /**
     * {@inheritDoc}
     */
    public function setPath($pattern)
    {
        $len = strlen($this->getStaticPrefix());

        if (strncmp($this->getStaticPrefix(), $pattern, $len)) {
            throw new \InvalidArgumentException('You can not set a pattern for the route document that does not match its repository path. First move it to the correct path.');
        }

        return $this->setVariablePattern(substr($pattern, $len));
    }

    /**
     * Return this routes children
     *
     * Filters out children that do not implement
     * the RouteObjectInterface.
     *
     * @return array - array of RouteObjectInterface's
     *
     */
    public function getRouteChildren()
    {
        $children = array();

        foreach ($this->children as $child) {
            if ($child instanceof RouteObjectInterface) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * {@inheritDoc}
     */
    protected function isBooleanOption($name)
    {
        return $name === 'add_trailing_slash' || parent::isBooleanOption($name);
    }
}
