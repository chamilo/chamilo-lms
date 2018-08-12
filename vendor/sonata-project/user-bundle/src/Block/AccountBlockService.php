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

use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractAdminBlockService;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Render a block with the connection option or the login name.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AccountBlockService extends AbstractAdminBlockService
{
    /**
     * @var TokenStorageInterface|SecurityContextInterface
     */
    private $tokenStorage;

    /**
     * NEXT_MAJOR: Go back to type hinting check when bumping requirements to SF 2.6+.
     *
     * @param string                                         $name
     * @param EngineInterface                                $templating
     * @param TokenStorageInterface|SecurityContextInterface $tokenStorage
     */
    public function __construct($name, EngineInterface $templating, $tokenStorage)
    {
        parent::__construct($name, $templating);

        if (!$tokenStorage instanceof TokenStorageInterface && !$tokenStorage instanceof SecurityContextInterface) {
            throw new \InvalidArgumentException(
                'Argument 3 should be an instance of Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface or Symfony\Component\Security\Core\SecurityContextInterface'
            );
        }

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $user = false;
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if (!$user instanceof UserInterface) {
            $user = false;
        }

        return $this->renderPrivateResponse($blockContext->getTemplate(), [
            'user' => $user,
            'block' => $blockContext->getBlock(),
            'context' => $blockContext,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'template' => 'SonataUserBundle:Block:account.html.twig',
            'ttl' => 0,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Account Block';
    }
}
