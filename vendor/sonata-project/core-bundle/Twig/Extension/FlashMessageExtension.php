<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

use Sonata\CoreBundle\FlashMessage\FlashManager;

/**
 * Class FlashMessageExtension
 *
 * This is the Sonata core flash message Twig extension
 *
 * @author Vincent Composieux <composieux@ekino.com>
 */
class FlashMessageExtension extends \Twig_Extension
{
    /**
     * @var FlashManager
     */
    protected $flashManager;

    /**
     * Constructor
     *
     * @param FlashManager $flashManager
     */
    public function __construct(FlashManager $flashManager)
    {
        $this->flashManager = $flashManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'sonata_flashmessages_get' => new \Twig_Function_Method($this, 'getFlashMessages'),
            'sonata_flashmessages_types' => new \Twig_Function_Method($this, 'getFlashMessagesTypes'),
        );
    }

    /**
     * Returns flash messages handled by Sonata core flash manager
     *
     * @param string $type   Type of flash message
     * @param string $domain Translation domain to use
     *
     * @return string
     */
    public function getFlashMessages($type, $domain = null)
    {
        return $this->flashManager->get($type, $domain);
    }

    /**
     * Returns flash messages types handled by Sonata core flash manager
     *
     * @param string $type   Type of flash message
     * @param string $domain Translation domain to use
     *
     * @return string
     */
    public function getFlashMessagesTypes()
    {
        return $this->flashManager->getHandledTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sonata_core_flashmessage';
    }
}
