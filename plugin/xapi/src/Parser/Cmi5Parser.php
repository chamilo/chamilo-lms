<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Cmi5Parser.
 *
 * @package Chamilo\PluginBundle\XApi\Parser
 */
class Cmi5Parser extends PackageParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(): ToolLaunch
    {
        $content = file_get_contents($this->filePath);
        $xml = new Crawler($content);

        $courseNode = $xml->filterXPath('//courseStructure/course');

        $toolLaunch = new ToolLaunch();
        $toolLaunch
            ->setTitle(
                current(
                    $this->getLanguageStrings(
                        $courseNode->filterXPath('//title')
                    )
                )
            )
            ->setDescription(
                current(
                    $this->getLanguageStrings(
                        $courseNode->filterXPath('//description')
                    )
                )
            )
            ->setLaunchUrl('')
            ->setActivityId($courseNode->attr('id'))
            ->setActivityType('cmi5')
            ->setAllowMultipleAttempts(false)
            ->setCreatedAt(api_get_utc_datetime(null, false, true))
            ->setCourse($this->course)
            ->setSession($this->session);

        $toc = $this->generateToC($xml);

        foreach ($toc as $cmi5Item) {
            $toolLaunch->addItem($cmi5Item);
        }

        return $toolLaunch;
    }

    /**
     * @return array
     */
    private function getLanguageStrings(Crawler $node)
    {
        $map = [];

        foreach ($node->children() as $child) {
            $key = $child->attributes['lang']->value;
            $value = trim($child->textContent);

            $map[$key] = $value;
        }

        return $map;
    }

    /**
     * @return array|\Chamilo\PluginBundle\Entity\XApi\Cmi5Item[]
     */
    private function generateToC(Crawler $xml)
    {
        $blocksMap = [];

        /** @var array|Cmi5Item[] $items */
        $items = $xml
            ->filterXPath('//*')
            ->reduce(
                function (Crawler $node, $i) {
                    return in_array($node->nodeName(), ['au', 'block']);
                }
            )
            ->each(
                function (Crawler $node, $i) use (&$blocksMap) {
                    $attributes = ['id', 'activityType', 'launchMethod', 'moveOn', 'masteryScore'];

                    list($id, $activityType, $launchMethod, $moveOn, $masteryMode) = $node->extract($attributes)[0];

                    $item = new Cmi5Item();
                    $item
                        ->setIdentifier($id)
                        ->setType($node->nodeName())
                        ->setTitle(
                            $this->getLanguageStrings(
                                $node->filterXPath('//title')
                            )
                        )
                        ->setDescription(
                            $this->getLanguageStrings(
                                $node->filterXPath('//description')
                            )
                        );

                    if ('au' === $node->nodeName()) {
                        $launchParametersNode = $node->filterXPath('//launchParameters');
                        $entitlementKeyNode = $node->filterXPath('//entitlementKey');
                        $url
                            = $item
                            ->setUrl(
                                $this->parseLaunchUrl(
                                    trim($node->filterXPath('//url')->text())
                                )
                            )
                            ->setActivityType($activityType ?: null)
                            ->setLaunchMethod($launchMethod ?: null)
                            ->setMoveOn($moveOn ?: 'NotApplicable')
                            ->setMasteryScore((float) $masteryMode ?: null)
                            ->setLaunchParameters(
                                $launchParametersNode->count() > 0 ? trim($launchParametersNode->text()) : null
                            )
                            ->setEntitlementKey(
                                $entitlementKeyNode->count() > 0 ? trim($entitlementKeyNode->text()) : null
                            );
                    }

                    $parentNode = $node->parents()->first();

                    if ('block' === $parentNode->nodeName()) {
                        $blocksMap[$i] = $parentNode->attr('id');
                    }

                    return $item;
                }
            );

        foreach ($blocksMap as $itemPos => $parentIdentifier) {
            foreach ($items as $item) {
                if ($parentIdentifier === $item->getIdentifier()) {
                    $items[$itemPos]->setParent($item);
                }
            }
        }

        return $items;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function parseLaunchUrl($url)
    {
        $urlInfo = parse_url($url);

        if (empty($urlInfo['scheme'])) {
            $baseUrl = str_replace(
                api_get_path(SYS_COURSE_PATH),
                api_get_path(WEB_COURSE_PATH),
                dirname($this->filePath)
            );

            return "$baseUrl/$url";
        }

        return $url;
    }
}
