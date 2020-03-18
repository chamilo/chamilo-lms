<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

/**
 * Class ReplaceFilePaths.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class ReplaceFilePaths extends LoadedCourseLookup
{
    /**
     * @throws \Exception
     *
     * @return string
     */
    public function transform(array $data)
    {
        list($content, $mCourseId) = array_values($data);

        if (empty($content)) {
            return '';
        }

        $cId = parent::transform([$mCourseId]);
        $courseInfo = api_get_course_info_by_id($cId);

        $doc = new \DOMDocument();
        $doc->loadHTML(
            mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')
        );

        foreach ($doc->getElementsByTagName('img') as $img) {
            $this->getNewSource('src', $img, $courseInfo['path']);
        }

        foreach ($doc->getElementsByTagName('a') as $a) {
            $this->getNewSource('href', $a, $courseInfo['path']);
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $bodyHtml = $doc->saveHTML($body);

        return $this->removeBodyTags($bodyHtml);
    }

    /**
     * @param string $attribute
     * @param string $coursePath
     *
     * @return string
     */
    private function getNewSource($attribute, \DOMElement $domElement, $coursePath)
    {
        $source = $domElement->getAttribute($attribute);

        if (empty($source) || strpos($source, '@@PLUGINFILE@@') === false) {
            return;
        }

        $parsedUrl = parse_url($source);
        $urlPath = $parsedUrl['path'];
        $urlQuery = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

        $fileName = basename($urlPath);
        $fileName = urldecode($fileName);
        $fileName = \URLify::filter($fileName, 250, '', true, true, false, false);

        $newSource = "/courses/$coursePath/document/$fileName"
            .(!empty($urlQuery) ? "?$urlQuery" : '');

        $domElement->setAttribute($attribute, $newSource);
    }

    /**
     * @param string $bodyHtml
     *
     * @return false|string
     */
    private function removeBodyTags($bodyHtml)
    {
        $tagStart = '<body>';
        $tagEnd = '</body>';

        return $content = substr(
            $bodyHtml,
            strlen($tagStart),
            -1 * strlen($tagEnd)
        );
    }
}
