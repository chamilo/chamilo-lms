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
        $content = file_get_contents($this->filePath);

        $xml = new Crawler($content);

        $activityNode = $xml->filter('tincan activities activity')->first();
        $nodeName = $activityNode->filter('name');
        $nodeDescription = $activityNode->filter('description');
        $nodeLaunch = $activityNode->filter('launch');

        $toolLaunch = new XApiToolLaunch();
        $toolLaunch
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreatedAt(api_get_utc_datetime(null, false, true))
            ->setActivityId($activityNode->attr('id'))
            ->setActivityType($activityNode->attr('type'))
            ->setLaunchUrl($this->parseLaunchUrl($nodeLaunch))
        ;

        if ($nodeName) {
            $toolLaunch->setTitle($nodeName->text());
        }

        if ($nodeDescription) {
            $toolLaunch->setDescription($nodeDescription->text() ?: null);
        }

        return $toolLaunch;
    }

    private function parseLaunchUrl(Crawler $launchNode): string
    {
        $launchUrl = $launchNode->text();

        $urlInfo = parse_url($launchUrl);

        if (empty($urlInfo['scheme'])) {
            $baseUrl = str_replace(
                api_get_path(SYS_COURSE_PATH),
                api_get_path(WEB_COURSE_PATH),
                \dirname($this->filePath)
            );

            return "$baseUrl/$launchUrl";
        }

        return $launchUrl;
    }
}
