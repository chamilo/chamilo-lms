<?php

namespace Chamilo\CoreBundle\Block;

use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\MenuBlockService;
use Sonata\ProductBundle\Menu\ProductMenuBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Templating\EngineInterface;

use Chamilo\CoreBundle\Menu\MainMenuBuilder;

/**
 * Class CategoriesMenuBlockService
 *
 * @package Sonata\ProductBundle\Block
 *
 */
class MainMenuBlockService extends MenuBlockService
{
    /**
     * @var MainMenuBuilder
     */
    private $menuBuilder;

    /**
     * Constructor
     *
     * @param string                $name
     * @param EngineInterface       $templating
     * @param MenuProviderInterface $menuProvider
     * @param ProductMenuBuilder    $menuBuilder
     */
    public function __construct($name, EngineInterface $templating, MenuProviderInterface $menuProvider, MainMenuBuilder $menuBuilder)
    {
        parent::__construct($name, $templating, $menuProvider, array());

        $this->menuBuilder = $menuBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Main Menu';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        parent::setDefaultSettings($resolver);

        $resolver->setDefaults(array(
            'menu_template' => "SonataBlockBundle:Block:block_side_menu_template.html.twig",
            'safe_labels'   => true
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMenu(BlockContextInterface $blockContext)
    {
        $settings = $blockContext->getSettings();

        $menu = parent::getMenu($blockContext);

        if (null === $menu || "" === $menu) {
            $menu = $this->menuBuilder->createCategoryMenu(
                array(
                    'childrenAttributes' => array('class' => $settings['menu_class']),
                    'attributes'         => array('class' => $settings['children_class']),
                ),
                $settings['current_uri']
            );
        }

        return $menu;
    }

}
