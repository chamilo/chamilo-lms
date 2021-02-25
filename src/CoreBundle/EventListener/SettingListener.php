<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Sylius\Bundle\SettingsBundle\Event\SettingsEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SettingListener.
 */
class SettingListener
{
    public function __construct()
    {
    }

    public function onSettingPreSave(SettingsEvent $event): void
    {
        /*$urlId = $this->container->get('request')->getSession()->get('access_url_id');
        $url = $this->container->get('doctrine')->getRepository('ChamiloCoreBundle:AccessUrl')->find($urlId);
        $settings = $event->getSettings();*/

        //$settings->setUrl($url);
        //$event->getSettings()->setAccessUrl($url);
        //$settings->setAccessUrl($url);
        //$event->setArgument('url', $url);
    }
}
