<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\XApiCmi5Item;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Cmi5Parser.
 */
class Cmi5Parser extends PackageParser
{
    public function parse(): XApiToolLaunch
    {
        $content = Container::$container->get(FileHelper::class)->read($this->filePath);
        $xml = new Crawler($content);

        $courseNode = $xml->filterXPath('//courseStructure/course');

        $toolLaunch = new XApiToolLaunch();
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
            ->setSession($this->session)
        ;

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
     * @return array<int, XApiCmi5Item>
     */
    private function generateToC(Crawler $xml): array
    {
        $blocksMap = [];

        /** @var array|XApiCmi5Item[] $items */
        $items = $xml
            ->filterXPath('//*')
            ->reduce(
                function (Crawler $node, $i) {
                    return \in_array($node->nodeName(), ['au', 'block']);
                }
            )
            ->each(
                function (Crawler $node, $i) use (&$blocksMap) {
                    $attributes = ['id', 'activityType', 'launchMethod', 'moveOn', 'masteryScore'];

                    list($id, $activityType, $launchMethod, $moveOn, $masteryMode) = $node->extract($attributes)[0];

                    $item = new XApiCmi5Item();
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
                        )
                    ;

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
                                )
                        ;
                    }

                    $parentNode = $node->parents()->first();

                    if ('block' === $parentNode->nodeName()) {
                        $blocksMap[$i] = $parentNode->attr('id');
                    }

                    return $item;
                }
            )
        ;

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
                \dirname($this->filePath)
            );

            return "$baseUrl/$url";
        }

        return $url;
    }
}
