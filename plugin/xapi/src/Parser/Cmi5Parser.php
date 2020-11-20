<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Symfony\Component\DomCrawler\Crawler;

class Cmi5Parser
{
    /**
     * @var string
     */
    private $filePath;
    /**
     * @var Course
     */
    private $course;
    /**
     * @var Session|null
     */
    private $session;
    /**
     * @var array
     */
    private $itemsByPosition;

    /**
     * Cmi5Parser constructor.
     *
     * @param string                                  $filePath
     * @param \Chamilo\CoreBundle\Entity\Course       $course
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     */
    protected function __construct($filePath, Course $course, Session $session = null)
    {
        $this->filePath = $filePath;
        $this->course = $course;
        $this->session = $session;
        $this->itemsByPosition = [];
    }

    /**
     * @param string                                  $filePath
     * @param \Chamilo\CoreBundle\Entity\Course       $course
     * @param \Chamilo\CoreBundle\Entity\Session|null $session
     *
     * @return \Chamilo\PluginBundle\XApi\Parser\Cmi5Parser
     */
    public static function create($filePath, Course $course, Session $session = null)
    {
        return new self($filePath, $course, $session);
    }

    public function parse()
    {
        $languageInterface = api_get_language_isocode(api_get_interface_language());

        $content = file_get_contents($this->filePath);

        $xml = new Crawler($content);

        $reduceFunction = function (Crawler $crawler) use ($languageInterface) {
            return strpos($crawler->attr('lang'), $languageInterface) === 0;
        };

        $courseStructure = $xml->first();
        $course = $courseStructure->filter('course')->first();
        $courseTitle = $course->filter('title langstring')->reduce($reduceFunction)->first();
        $courseDescription = $course->filter('description langstring')->reduce($reduceFunction)->first();

        $unitsAndBlocks = $courseStructure
            ->filter('block, au')
            ->each(function (Crawler $node) use ($reduceFunction) {
                $item = [
                    'type' => 'cmi5_'.$node->nodeName(),
                    'id' => $node->attr('id'),
                    'title' => $node->filter('title langstring')->reduce($reduceFunction)->text(),
                    'description' => $node->filter('description langstring')->reduce($reduceFunction)->text(),
                    'parent' => '',
                    'previous' => '',
                ];

                $parentNode = $node->parents()->first();

                if ($parentNode->nodeName() === 'block') {
                    $item['parent'] = $parentNode->attr('id');
                }

                $previousNode = $node->previousAll()->reduce(
                    function (Crawler $previous) {
                        return in_array($previous->nodeName(), ['block', 'au']);
                    }
                );

                if ($previousNode->count() > 0) {
                    $item['previous'] = $previousNode->attr('id');
                }

                if ($node->nodeName() === 'au') {
                    $item['url'] = $node->filter('url')->text();
                    $item['mastery_score'] = $node->attr('masteryScore');
                    $item['activity_type'] = $node->attr('activityType');
                    $item['params'] = $node->filter('launchParameters')->getNode(0)
                        ? $node->filter('launchParameters')->getNode(0)->nodeValue
                        : null;
                }

                return array_map('trim', $item);
            });

        $this->createLearningPath(
            $courseTitle->text(),
            $courseDescription->text(),
            $unitsAndBlocks
        );
    }

    private function createLearningPath($title, $description, array $items)
    {
        $em = \Database::getManager();

        $lpId = \learnpath::add_lp(
            $this->course->getCode(),
            $title,
            $description,
            'cmi5',
            'manual'
        );

        $authorId = api_get_user_id();
        $courseInfo = api_get_course_info_by_id($this->course->getId());

        $lp = new \learnpath(
            $this->course->getCode(),
            $lpId,
            $authorId
        );
        $lp->generate_lp_folder($courseInfo);

        $itemsIds = array_column($items, 'id');

        foreach ($items as $i => $item) {
            $parentPosition = !empty($item['parent']) ? array_search($item['parent'], $itemsIds) : -1;
            $previousPosition = !empty($item['previous']) ? array_search($item['previous'], $itemsIds) : -1;

            $lpItemId = $lp->add_item(
                isset($this->itemsByPosition[$parentPosition]) ? $this->itemsByPosition[$parentPosition] : 0,
                isset($this->itemsByPosition[$previousPosition]) ? $this->itemsByPosition[$previousPosition] : 0,
                $item['type'],
                0,
                $item['title'],
                $item['description']
            );

            $cmi5Item = new Cmi5Item();
            $cmi5Item
                ->setActivityType($item['activity_type'])
                ->setUrl(
                    $this->parseLaunchUrl($item['url'])
                );

            $em->persist($cmi5Item);
            $em->flush();

            \Database::update(
                \Database::get_course_table(TABLE_LP_ITEM),
                [
                    'parameters' => isset($item['params']) ? $item['params'] : null,
                    'mastery_score' => isset($item['mastery_score']) ? $item['mastery_score'] : null,
                    'path' => $cmi5Item->getId(),
                ],
                ['iid = ?' => $lpItemId]
            );

            $this->itemsByPosition[$i] = $lpItemId;
        }
    }

    /**
     * @param string $launchUrl
     *
     * @return string
     */
    private function parseLaunchUrl($launchUrl)
    {
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
