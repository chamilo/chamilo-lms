<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class TinCanParser.
 *
 * @package Chamilo\PluginBundle\XApi\Parser
 */
class TinCanParser extends AbstractParser
{
    /**
     * @inheritDoc
     */
    public static function create($filePath, Course $course, Session $session = null)
    {
        return new self($filePath, $course, $session);
    }

    /**
     * @inheritDoc
     */
    public function parse()
    {
        $content = file_get_contents($this->filePath);

        $xml = new Crawler($content);

        $activityNode = $xml->filter('tincan activities activity')->first();
        $nodeName = $activityNode->filter('name');
        $nodeDescription = $activityNode->filter('description');
        $nodeLaunch = $activityNode->filter('launch');

        $toolLaunch = new ToolLaunch();
        $toolLaunch
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreatedAt(api_get_utc_datetime(null, false, true))
            ->setActivityId($activityNode->attr('id'))
            ->setActivityType($activityNode->attr('type'))
            ->setLaunchUrl($this->parseLaunchUrl($nodeLaunch));

        if ($nodeName) {
            $toolLaunch->setTitle($nodeName->text());
        }

        if ($nodeDescription) {
            $toolLaunch->setDescription($nodeDescription->text() ?: null);
        }

        return $toolLaunch;
    }

    /**
     * @param \Symfony\Component\DomCrawler\Crawler $launchNode
     *
     * @return string
     */
    private function parseLaunchUrl(Crawler $launchNode)
    {
        $launchUrl = $launchNode->text();

        $urlInfo = parse_url($launchUrl);

        if (empty($urlInfo['scheme'])) {
            $baseUrl = str_replace(
                api_get_path(SYS_COURSE_PATH),
                api_get_path(WEB_COURSE_PATH),
                dirname($this->filePath)
            );

            return "$baseUrl/$launchUrl";
        }

        return $launchUrl;
    }
}
