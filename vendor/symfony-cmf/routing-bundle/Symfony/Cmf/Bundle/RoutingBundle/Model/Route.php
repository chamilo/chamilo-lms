<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Model;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Default model for routing table entries that work with the DynamicRouter.
 *
 * @author david.buchmann@liip.ch
 */
class Route extends SymfonyRoute implements RouteObjectInterface
{
    /**
     * Unique id of this route
     *
     * @var string
     */
    protected $id;

    /**
     * The referenced content object
     *
     * @var object
     */
    protected $content;

    /**
     * Part of the URL that does not have parameters and thus can be used to
     * naivly guess candidate routes.
     *
     * Note that this field is not used by PHPCR-ODM
     *
     * @var string
     */
    protected $staticPrefix;

    /**
     * Variable pattern part. The static part of the pattern is the id without the prefix.
     *
     * @var string
     */
    protected $variablePattern;

    /**
     * Whether this route was changed since being last compiled.
     *
     * State information not persisted in storage.
     *
     * @var Boolean
     */
    protected $needRecompile = false;

    /**
     * Overwrite to be able to create route without pattern
     *
     * Additional supported options are:
     *
     * * add_format_pattern: When set, ".{_format}" is appended to the route pattern.
     *                       Also implicitly sets a default/require on "_format" to "html".
     * * add_locale_pattern: When set, "/{_locale}" is prepended to the route pattern.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setDefaults(array());
        $this->setRequirements(array());
        $this->setOptions($options);

        if ($this->getOption('add_format_pattern')) {
            $this->setDefault('_format', 'html');
            $this->setRequirement('_format', 'html');
        }
    }

    /**
     * @deprecated use getOption('add_format_pattern') instead
     */
    public function getAddFormatPattern()
    {
        return $this->getOption('add_format_pattern');
    }

    /**
     * @deprecated use setOption('add_format_pattern', $bool) instead
     */
    public function setAddFormatPattern($addFormatPattern)
    {
        $this->setOption('add_format_pattern', $addFormatPattern);
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteKey()
    {
        return $this->getId();
    }

    /**
     * Get the repository path of this url entry
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string the static prefix part of this route
     */
    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }

    /**
     * @param string $prefix The static prefix part of this route
     *
     * @return Route $this
     */
    public function setStaticPrefix($prefix)
    {
        $this->staticPrefix = $prefix;

        return $this;
    }

    /**
     * Set the object this url points to
     *
     * @param mixed $object A content object that can be persisted by the
     *                      storage layer.
     */
    public function setContent($object)
    {
        $this->content = $object;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     *
     * Prevent setting the default 'compiler_class' so that we do not persist it
     */
    public function setOptions(array $options)
    {
        return $this->addOptions($options);
    }

    /**
     * {@inheritDoc}
     *
     * Handling the missing default 'compiler_class'
     * @see setOptions
     */
    public function getOption($name)
    {
        $option = parent::getOption($name);
        if (null === $option && 'compiler_class' === $name) {
            return 'Symfony\\Component\\Routing\\RouteCompiler';
        }
        if ($this->isBooleanOption($name)) {
            return (boolean) $option;
        }

        return $option;
    }

    /**
     * {@inheritDoc}
     *
     * Handling the missing default 'compiler_class'
     * @see setOptions
     */
    public function getOptions()
    {
        $options = parent::getOptions();
        if (!array_key_exists('compiler_class', $options)) {
            $options['compiler_class'] = 'Symfony\\Component\\Routing\\RouteCompiler';
        }
        foreach ($options as $key => $value) {
            if ($this->isBooleanOption($key)) {
                $options[$key] = (boolean) $value;
            }
        }

        return $options;
    }

    /**
     * Helper method to check if an option is a boolean option to allow better forms.
     *
     * @param string $name
     */
    protected function isBooleanOption($name)
    {
        return in_array($name, array('add_format_pattern', 'add_locale_pattern'));
    }

    /**
     * We need to overwrite this to avoid issues with the legacy code in
     * SymfonyRoute.
     *
     * @deprecated Use getPath instead.
     */
    public function getPattern()
    {
        return $this->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        $pattern = '';
        if ($this->getOption('add_locale_pattern')) {
            $pattern .= '/{_locale}';
        }
        $pattern .= $this->getStaticPrefix();
        $pattern .= $this->getVariablePattern();
        if ($this->getOption('add_format_pattern') && !preg_match('/(.+)\.[a-z]+$/i', $pattern, $matches)) {
            $pattern .= '.{_format}';
        };

        return $pattern;
    }

    /**
     * {@inheritDoc}
     *
     * It is recommended to use setVariablePattern to just set the part after
     * the static part. If you use this method, it will ensure that the
     * static part is not changed and only change the variable part.
     *
     * When using PHPCR-ODM, make sure to persist the route before calling this
     * to have the id field initialized.
     */
    public function setPath($pattern)
    {
        $len = strlen($this->getStaticPrefix());

        if (strncmp($this->getStaticPrefix(), $pattern, $len)) {
            throw new \InvalidArgumentException('You can not set a pattern for the route that does not start with its current static prefix. First update the static prefix or directly use setVariablePattern.');
        }

        return $this->setVariablePattern(substr($pattern, $len));
    }

    /**
     * @return string the variable part of the url pattern
     */
    public function getVariablePattern()
    {
        return $this->variablePattern;
    }

    /**
     * @param string $variablePattern the variable part of the url pattern
     *
     * @return Route
     */
    public function setVariablePattern($variablePattern)
    {
        $this->variablePattern = $variablePattern;
        $this->needRecompile = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * Overwritten to make sure the route is recompiled if the pattern was changed
     */
    public function compile()
    {
        if ($this->needRecompile) {
            // calling parent::setPath just to let it set compiled=null. the parent $path field is never used
            parent::setPath($this->getPath());
        }

        return parent::compile();
    }
}
