<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\FlashMessage;

use Sonata\CoreBundle\Component\Status\StatusClassRendererInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FlashManager
 *
 * @author Vincent Composieux <composieux@ekino.com>
 */
class FlashManager implements StatusClassRendererInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $types;

    /**
     * @var array
     */
    protected $cssClasses;

    /**
     * Constructor
     *
     * @param SessionInterface    $session    Symfony session service
     * @param TranslatorInterface $translator Symfony translator service
     * @param array               $types      Sonata core types array (defined in configuration)
     * @param array               $cssClasses Css classes associated with $types
     */
    public function __construct(SessionInterface $session, TranslatorInterface $translator, array $types, array $cssClasses)
    {
        $this->session    = $session;
        $this->translator = $translator;
        $this->types      = $types;
        $this->cssClasses = $cssClasses;
    }

    /**
     * {@inheritdoc}
     */
    public function handlesObject($object, $statusName = null)
    {
        return is_string($object) && array_key_exists($object, $this->cssClasses);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusClass($object, $statusName = null, $default = "")
    {
        return array_key_exists($object, $this->cssClasses)
            ? $this->cssClasses[$object]
            : $default;
    }


    /**
     * Returns Sonata core flash message types
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Returns Symfony session service
     *
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns Symfony translator service
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Returns flash bag messages for correct type after renaming with Sonata core type
     *
     * @param string $type   Type of flash message
     * @param string $domain Translation domain to use
     *
     * @return array
     */
    public function get($type, $domain = null)
    {
        $this->handle($domain);

        return $this->getSession()->getFlashBag()->get($type);
    }

    /**
     * Gets handled message types
     *
     * @return array
     */
    public function getHandledTypes()
    {
        return array_keys($this->getTypes());
    }

    /**
     * Handles flash bag types renaming
     *
     * @param string $domain
     *
     * @return void
     */
    protected function handle($domain = null)
    {
        foreach ($this->getTypes() as $type => $values) {
            foreach ($values as $value => $options) {
                $domainType = $domain ?: $options['domain'];
                $this->rename($type, $value, $domainType);
            }
        }
    }

    /**
     * Process flash message type rename
     *
     * @param string $type   Sonata core flash message type
     * @param string $value  Original flash message type
     * @param string $domain Translation domain to use
     *
     * @return void
     */
    protected function rename($type, $value, $domain)
    {
        $flashBag = $this->getSession()->getFlashBag();

        foreach ($flashBag->get($value) as $message) {
            $message = $this->getTranslator()->trans($message, array(), $domain);
            $flashBag->add($type, $message);
        }
    }
}
