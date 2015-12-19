<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\BlockBundle\Block\Service;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MenuBlockService
 *
 * @package Sonata\BlockBundle\Block\Service
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class MenuBlockService extends BaseBlockService
{
    /**
     * @var MenuProviderInterface
     */
    protected $menuProvider;

    /**
     * @var array
     */
    protected $menus;

    /**
     * Constructor
     *
     * @param string                $name
     * @param EngineInterface       $templating
     * @param MenuProviderInterface $menuProvider
     * @param array                 $menus
     */
    public function __construct($name, EngineInterface $templating, MenuProviderInterface $menuProvider, array $menus = array())
    {
        parent::__construct($name, $templating);

        $this->menuProvider = $menuProvider;
        $this->menus        = $menus;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $responseSettings = array(
            'menu'         => $this->getMenu($blockContext),
            'menu_options' => $this->getMenuOptions($blockContext->getSettings()),
            'block'        => $blockContext->getBlock(),
            'context'      => $blockContext
        );

        if ('private' === $blockContext->getSettings('cache_policy')) {
            return $this->renderPrivateResponse($blockContext->getTemplate(), $responseSettings, $response);
        }

        return $this->renderResponse($blockContext->getTemplate(), $responseSettings, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $form, BlockInterface $block)
    {
        $form->add('settings', 'sonata_type_immutable_array', array(
            'keys' => $this->getFormSettingsKeys()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
        if (($name = $block->getSetting('menu_name')) && $name !== "" && !$this->menuProvider->has($name)) {
            // If we specified a menu_name, check that it exists
            $errorElement->with('menu_name')
                ->addViolation('sonata.block.menu.not_existing', array('name' => $name))
            ->end();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'title'          => $this->getName(),
            'cache_policy'   => 'public',
            'template'       => 'SonataBlockBundle:Block:block_core_menu.html.twig',
            'menu_name'      => "",
            'safe_labels'    => false,
            'current_class'  => 'active',
            'first_class'    => false,
            'last_class'     => false,
            'current_uri'    => null,
            'menu_class'     => "list-group",
            'children_class' => "list-group-item",
            'menu_template'  => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Menu';
    }

    /**
     * @return array
     */
    protected function getFormSettingsKeys()
    {
        return array(
            array('title', 'text', array('required' => false)),
            array('cache_policy', 'choice', array('choices' => array('public', 'private'))),
            array('menu_name', 'choice', array('choices' => $this->menus, 'required' => false)),
            array('safe_labels', 'checkbox', array('required' => false)),
            array('current_class', 'text', array('required' => false)),
            array('first_class', 'text', array('required' => false)),
            array('last_class', 'text', array('required' => false)),
            array('menu_class', 'text', array('required' => false)),
            array('children_class', 'text', array('required' => false)),
            array('menu_template', 'text', array('required' => false)),
        );
    }

    /**
     * Gets the menu to render
     *
     * @param BlockContextInterface $blockContext
     *
     * @return ItemInterface|string
     */
    protected function getMenu(BlockContextInterface $blockContext)
    {
        $settings = $blockContext->getSettings();

        return $settings['menu_name'];
    }

    /**
     * Replaces setting keys with knp menu item options keys
     *
     * @param array $settings
     *
     * @return array
     */
    protected function getMenuOptions(array $settings)
    {
        $mapping = array(
            'current_class' => 'currentClass',
            'first_class'   => 'firstClass',
            'last_class'    => 'lastClass',
            'safe_labels'   => 'allow_safe_labels',
            'menu_template' => 'template',
        );

        $options = array();

        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $mapping) && null !== $value) {
                $options[$mapping[$key]] = $value;
            }
        }

        return $options;
    }
}