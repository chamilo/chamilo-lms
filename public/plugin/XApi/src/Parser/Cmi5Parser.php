<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Parser;

use Chamilo\CoreBundle\Entity\XApiCmi5Item;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;

/**
 * Class Cmi5Parser.
 */
class Cmi5Parser extends PackageParser
{
    public function parse(): XApiToolLaunch
    {
        $dom = new DOMDocument();

        $content = file_get_contents($this->filePath);
        if (false === $content || '' === trim($content)) {
            throw new Exception('Unable to read cmi5.xml.');
        }

        libxml_use_internal_errors(true);

        if (!$dom->loadXML($content)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $message = 'Invalid cmi5.xml.';
            if (!empty($errors)) {
                $message .= ' '.trim($errors[0]->message);
            }

            throw new Exception($message);
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $courseNode = $xpath->query('/*[local-name()="courseStructure"]/*[local-name()="course"]')->item(0);

        if (!$courseNode instanceof DOMElement) {
            throw new Exception('Invalid cmi5 package: course node not found.');
        }

        $titleMap = $this->getChildLanguageStrings($courseNode, 'title');
        $descriptionMap = $this->getChildLanguageStrings($courseNode, 'description');

        $toolLaunch = new XApiToolLaunch();
        $toolLaunch
            ->setTitle(!empty($titleMap) ? (string) current($titleMap) : 'cmi5')
            ->setDescription(!empty($descriptionMap) ? (string) current($descriptionMap) : null)
            ->setLaunchUrl('')
            ->setActivityId((string) $courseNode->getAttribute('id'))
            ->setActivityType('cmi5')
            ->setAllowMultipleAttempts(false)
            ->setCreatedAt(api_get_utc_datetime(null, false, true))
            ->setCourse($this->course)
            ->setSession($this->session)
        ;

        $toc = $this->generateToC($xpath);

        foreach ($toc as $cmi5Item) {
            $toolLaunch->addItem($cmi5Item);
        }

        return $toolLaunch;
    }

    /**
     * @return array<string, string>
     */
    private function getChildLanguageStrings(DOMElement $element, string $childName): array
    {
        $map = [];

        foreach ($element->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childName !== $childNode->localName) {
                continue;
            }

            foreach ($childNode->childNodes as $langNode) {
                if (!$langNode instanceof DOMElement) {
                    continue;
                }

                if ('langstring' !== strtolower((string) $langNode->localName)) {
                    continue;
                }

                $lang = trim((string) $langNode->getAttribute('lang'));
                $value = trim((string) $langNode->textContent);

                if ('' === $lang) {
                    $lang = 'und';
                }

                $map[$lang] = $value;
            }

            break;
        }

        return $map;
    }

    private function getDirectChildText(DOMElement $element, string $childName): ?string
    {
        foreach ($element->childNodes as $childNode) {
            if (!$childNode instanceof DOMElement) {
                continue;
            }

            if ($childName !== $childNode->localName) {
                continue;
            }

            $value = trim((string) $childNode->textContent);

            return '' !== $value ? $value : null;
        }

        return null;
    }

    /**
     * @return array<int, XApiCmi5Item>
     */
    private function generateToC(DOMXPath $xpath): array
    {
        $blocksMap = [];
        $items = [];

        $nodes = $xpath->query('//*[local-name()="au" or local-name()="block"]');

        if (false === $nodes) {
            return [];
        }

        foreach ($nodes as $index => $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $identifier = trim((string) $node->getAttribute('id'));
            $activityType = trim((string) $node->getAttribute('activityType'));
            $launchMethod = trim((string) $node->getAttribute('launchMethod'));
            $moveOn = trim((string) $node->getAttribute('moveOn'));
            $masteryScore = trim((string) $node->getAttribute('masteryScore'));

            $titleMap = $this->getChildLanguageStrings($node, 'title');
            $descriptionMap = $this->getChildLanguageStrings($node, 'description');

            $item = new XApiCmi5Item();
            $item
                ->setIdentifier($identifier)
                ->setType((string) $node->localName)
                ->setTitle($titleMap)
                ->setDescription($descriptionMap)
            ;

            if ('au' === $node->localName) {
                $rawUrl = $this->getDirectChildText($node, 'url');
                $resolvedUrl = null;

                if (null !== $rawUrl) {
                    $resolvedUrl = $this->parseLaunchUrl($rawUrl);
                }

                $item
                    ->setUrl($resolvedUrl)
                    ->setActivityType('' !== $activityType ? $activityType : null)
                    ->setLaunchMethod('' !== $launchMethod ? $launchMethod : null)
                    ->setMoveOn('' !== $moveOn ? $moveOn : 'NotApplicable')
                    ->setMasteryScore('' !== $masteryScore ? (float) $masteryScore : null)
                    ->setLaunchParameters($this->getDirectChildText($node, 'launchParameters'))
                    ->setEntitlementKey($this->getDirectChildText($node, 'entitlementKey'))
                ;
            }

            $parentNode = $this->getParentElement($node);

            if ($parentNode instanceof DOMElement && 'block' === $parentNode->localName) {
                $blocksMap[$index] = trim((string) $parentNode->getAttribute('id'));
            }

            $items[$index] = $item;
        }

        foreach ($blocksMap as $itemPos => $parentIdentifier) {
            foreach ($items as $item) {
                if ($parentIdentifier === $item->getIdentifier()) {
                    $items[$itemPos]->setParent($item);
                    break;
                }
            }
        }

        return array_values($items);
    }

    private function parseLaunchUrl(string $url): string
    {
        return $this->resolvePackageUrl($url);
    }

    private function getParentElement(DOMElement $node): ?DOMElement
    {
        $parentNode = $node->parentNode;

        while ($parentNode instanceof DOMNode && !$parentNode instanceof DOMElement) {
            $parentNode = $parentNode->parentNode;
        }

        return $parentNode instanceof DOMElement ? $parentNode : null;
    }
}
