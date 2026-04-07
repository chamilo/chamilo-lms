<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class TinCanParser.
 */
class TinCanParser extends PackageParser
{
    public function parse(): XApiToolLaunch
    {
        $xml = new Crawler($this->readPackageFileContents());

        $activityNode = $xml->filter('tincan activities activity')->first();
        $nodeName = $activityNode->filter('name');
        $nodeDescription = $activityNode->filter('description');
        $nodeLaunch = $activityNode->filter('launch');

        $toolLaunch = new XApiToolLaunch();
        $toolLaunch
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreatedAt(api_get_utc_datetime(null, false, true))
            ->setActivityId((string) $activityNode->attr('id'))
            ->setActivityType((string) $activityNode->attr('type'))
            ->setLaunchUrl($this->parseLaunchUrl($nodeLaunch))
        ;

        if ($nodeName->count() > 0) {
            $toolLaunch->setTitle($nodeName->text());
        }

        if ($nodeDescription->count() > 0) {
            $toolLaunch->setDescription($nodeDescription->text() ?: null);
        }

        return $toolLaunch;
    }

    private function parseLaunchUrl(Crawler $launchNode): string
    {
        return $this->resolvePackageUrl($launchNode->text());
    }
}
