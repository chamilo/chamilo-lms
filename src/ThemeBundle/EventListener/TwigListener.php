<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TwigListener implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $container = $this->container;

        Container::setContainer($container);
        Container::setLegacyServices($container);

        $theme = api_get_visual_theme();
        $twig = $container->get('twig');
        $twig->addGlobal('favico', \Template::getPortalIcon($theme));
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}
