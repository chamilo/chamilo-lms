<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block\Service;

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractBlockService implements BlockServiceInterface
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * NEXT_MAJOR: Remove this property.
     *
     * @var EngineInterface|null
     */
    protected $templating;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * NEXT_MAJOR: Make `$twig` argument mandatory and remove other arguments.
     *
     * @param Environment|EngineInterface|string $templatingOrDeprecatedName
     */
    public function __construct($templatingOrDeprecatedName = null, ?EngineInterface $templating = null)
    {
        // $this->twig = $twig;
        // NEXT_MAJOR: Uncomment the previous assignment and remove the following lines in this method.

        if ($templatingOrDeprecatedName instanceof Environment) {
            $this->name = '';
            $this->twig = $templatingOrDeprecatedName;
        } else {
            if (0 !== strpos(static::class, __NAMESPACE__.'\\')) {
                $class = 'c' === static::class[0] && 0 === strpos(static::class, "class@anonymous\0") ? get_parent_class(static::class).'@anonymous' : static::class;

                @trigger_error(
                    sprintf(
                        'Passing %s as argument 1 to %s::%s() is deprecated since sonata-project/block-bundle 3.16 and will throw a \TypeError as of 4.0. You must pass an instance of %s instead.',
                        \is_object($templatingOrDeprecatedName) ? 'instance of '.\get_class($templatingOrDeprecatedName) : \gettype($templatingOrDeprecatedName),
                        $class,
                        __FUNCTION__,
                        Environment::class
                    ),
                    E_USER_DEPRECATED
                );
            }

            if ($templatingOrDeprecatedName instanceof EngineInterface) {
                $this->name = '';
                $this->templating = $templatingOrDeprecatedName;
            } elseif (\is_string($templatingOrDeprecatedName)) {
                $this->name = $templatingOrDeprecatedName;
                $this->templating = $templating;
            } else {
                $class = 'c' === static::class[0] && 0 === strpos(static::class, "class@anonymous\0") ? get_parent_class(static::class).'@anonymous' : static::class;

                throw new \TypeError(sprintf(
                    'Argument 1 passed to %s::%s() must be a string or an instance of %s or %s, %s given.',
                    $class,
                    __FUNCTION__,
                    Environment::class,
                    EngineInterface::class,
                    \is_object($templatingOrDeprecatedName) ? 'instance of '.\get_class($templatingOrDeprecatedName) : \gettype($templatingOrDeprecatedName)
                ));
            }
        }
    }

    /**
     * Returns a Response object than can be cacheable.
     *
     * @param string $view
     *
     * @return Response
     */
    public function renderResponse($view, array $parameters = [], Response $response = null)
    {
        if (null === $this->twig) {
            return $this->getTemplating()->renderResponse($view, $parameters, $response);
        }

        // NEXT_MAJOR: Remove the previous condition
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->twig->render($view, $parameters));

        return $response;
    }

    /**
     * Returns a Response object that cannot be cacheable, this must be used if the Response is related to the user.
     * A good solution to make the page cacheable is to configure the block to be cached with javascript ...
     *
     * @param string $view
     *
     * @return Response
     */
    public function renderPrivateResponse($view, array $parameters = [], Response $response = null)
    {
        return $this->renderResponse($view, $parameters, $response)
            ->setTtl(0)
            ->setPrivate()
        ;
    }

    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        if (!$resolver instanceof OptionsResolver) {
            throw new \BadMethodCallException(
                sprintf('Calling %s with %s is unsupported', __METHOD__, \get_class($resolver))
            );
        }

        $this->configureSettings($resolver);
    }

    /**
     * Define the default options for the block.
     */
    public function configureSettings(OptionsResolver $resolver)
    {
    }

    public function getCacheKeys(BlockInterface $block)
    {
        return [
            'block_id' => $block->getId(),
            'updated_at' => $block->getUpdatedAt() ? $block->getUpdatedAt()->format('U') : strtotime('now'),
        ];
    }

    public function load(BlockInterface $block)
    {
    }

    public function getJavascripts($media)
    {
        return [];
    }

    public function getStylesheets($media)
    {
        return [];
    }

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse($blockContext->getTemplate(), [
            'block_context' => $blockContext,
            'block' => $blockContext->getBlock(),
        ], $response);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/block-bundle 3.17
     */
    public function getTemplating()
    {
        $class = 'c' === static::class[0] && 0 === strpos(static::class, "class@anonymous\0") ? get_parent_class(static::class).'@anonymous' : static::class;

        @trigger_error(
            sprintf(
                'Method %s::%s() is deprecated since sonata-project/block-bundle 3.17 and will be removed as of version 4.0.',
                $class,
                __FUNCTION__
            ),
            E_USER_DEPRECATED
        );

        if (null !== $this->twig) {
            throw new \BadMethodCallException(sprintf(
                'Calling %1$s::%2$s() is not allowed when an instance of %3$s is passed as argument 1 to %1$s::__construct().',
                $class,
                __FUNCTION__,
                Environment::class
            ));
        }

        return $this->templating;
    }
}
