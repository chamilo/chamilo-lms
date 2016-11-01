<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Block;

use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\UserBundle\Menu\ProfileMenuBuilder;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class AccountBlockService.
 *
 * Render a block with the connection option or the login name
 *
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AccountBlockService extends BaseBlockService
{
    /**
     * @var ProfileMenuBuilder
     */
    private $securityContext;

    /**
     * Constructor.
     *
     * @param string                   $name
     * @param EngineInterface          $templating
     * @param SecurityContextInterface $securityContext
     */
    public function __construct($name, EngineInterface $templating, SecurityContextInterface $securityContext)
    {
        parent::__construct($name, $templating);

        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $user = false;
        if ($this->securityContext->getToken()) {
            $user = $this->securityContext->getToken()->getUser();
        }

        if (!$user instanceof UserInterface) {
            $user = false;
        }

        return $this->renderPrivateResponse($blockContext->getTemplate(), array(
            'user' => $user,
            'block' => $blockContext->getBlock(),
            'context' => $blockContext,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'template' => 'SonataUserBundle:Block:account.html.twig',
            'ttl' => 0,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Account Block';
    }
}
