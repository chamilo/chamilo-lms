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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\HierarchyInterface;
use Doctrine\ODM\PHPCR\Document\Generic;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Model\Route as RouteModel;

/**
 * PHPCR-ODM routes use their path for the url. They need to have set the
 * IdPrefix on loading to represent the correct URL.
 *
 * @author david.buchmann@liip.ch
 */
class Route extends RouteModel implements PrefixInterface, HierarchyInterface
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
     * PHPCR id can not end on '/', so we need an additional option for a
     * trailing slash.
     *
     * Additional supported option is:
     *
     * * add_trailing_slash: When set, a trailing slash is appended to the route
     */
    public function __construct(array $options= array())
    {
        parent::__construct($options);

        $this->children = new ArrayCollection();
    }

    /**
     * @deprecated use getOption('add_trailing_slash') instead
     */
    public function getAddTrailingSlash()
    {
        return $this->getOption('add_trailing_slash');
    }

    /**
     * @deprecated use setOption('add_trailing_slash', $add) instead
     */
    public function setAddTrailingSlash($addTrailingSlash)
    {
        $this->setOption('add_trailing_slash', $addTrailingSlash);
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
     */
    public function setParentDocument($parent)
    {
        if (!is_object($parent)) {
            throw new InvalidArgumentException("Parent must be an object ".gettype ($parent)." given.");
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * The parent document, which might be another route or some other
     * document.
     *
     * @return object The parent document
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
     *
     * @return self
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
        if (!is_object($parent)) {
            throw new InvalidArgumentException("Parent must be an object ".gettype ($parent)." given.");
        }

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
        $path = $this->getId();
        $prefix = $this->getPrefix();

        return $this->generateStaticPrefix($path, $prefix);
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
     *
     * Handle the trailing slash option.
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
     * Get all route children of this route.
     *
     * Filters out children that do not implement the RouteObjectInterface.
     *
     * @return RouteObjectInterface[]
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
     * Get all children of this route including non-routes.
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    protected function isBooleanOption($name)
    {
        return $name === 'add_trailing_slash' || parent::isBooleanOption($name);
    }
}
