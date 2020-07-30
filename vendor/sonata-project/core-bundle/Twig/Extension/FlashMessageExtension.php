<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Twig\Extension;

use Sonata\CoreBundle\FlashMessage\FlashManager;

/**
 * This is the Sonata core flash message Twig extension.
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
            new \Twig_SimpleFunction('sonata_flashmessages_get', array($this, 'getFlashMessages')),
            new \Twig_SimpleFunction('sonata_flashmessages_types', array($this, 'getFlashMessagesTypes')),
        );
    }

    /**
     * Returns flash messages handled by Sonata core flash manager.
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
     * Returns flash messages types handled by Sonata core flash manager.
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
    public function getName()
    {
        return 'sonata_core_flashmessage';
    }
}
