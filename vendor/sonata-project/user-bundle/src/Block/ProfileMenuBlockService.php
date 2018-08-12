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

use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\MenuBlockService;
use Sonata\UserBundle\Menu\ProfileMenuBuilder;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class ProfileMenuBlockService extends MenuBlockService
{
    /**
     * @var ProfileMenuBuilder
     */
    private $menuBuilder;

    /**
     * @param string                $name
     * @param EngineInterface       $templating
     * @param MenuProviderInterface $menuProvider
     * @param ProfileMenuBuilder    $menuBuilder
     */
    public function __construct($name, EngineInterface $templating, MenuProviderInterface $menuProvider, ProfileMenuBuilder $menuBuilder)
    {
        parent::__construct($name, $templating, $menuProvider, []);

        $this->menuBuilder = $menuBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'User Profile Menu';
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);

        $resolver->setDefaults([
            'cache_policy' => 'private',
            'menu_template' => 'SonataBlockBundle:Block:block_side_menu_template.html.twig',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMenu(BlockContextInterface $blockContext)
    {
        $settings = $blockContext->getSettings();

        $menu = parent::getMenu($blockContext);

        if (null === $menu || '' === $menu) {
            $menu = $this->menuBuilder->createProfileMenu(
                [
                    'childrenAttributes' => ['class' => $settings['menu_class']],
                    'attributes' => ['class' => $settings['children_class']],
                ]
            );

            if (method_exists($menu, 'setCurrentUri')) {
                $menu->setCurrentUri($settings['current_uri']);
            }
        }

        return $menu;
    }
}
