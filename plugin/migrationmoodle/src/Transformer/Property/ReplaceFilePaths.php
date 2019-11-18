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
     * @param array $data
     *
     * @throws \Exception
     *
     * @return string
     */
    public function transform(array $data)
    {
        list($content, $mCourseId) = array_values($data);

        $cId = parent::transform([$mCourseId]);
        $courseInfo = api_get_course_info_by_id($cId);

        $doc = new \DOMDocument();
        $doc->loadHTML(
            mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')
        );

        /** @var \DOMElement $img */
        foreach ($doc->getElementsByTagName('img') as $img) {
            $source = $img->getAttribute('src');
            $newSource = $this->getNewSource($source, $courseInfo['path']);

            $img->setAttribute('src', $newSource);
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $bodyHtml = $doc->saveHTML($body);

        return $this->removeBodyTags($bodyHtml);
    }

    /**
     * @param string $source
     * @param string $coursePath
     *
     * @return string
     */
    private function getNewSource($source, $coursePath)
    {
        $fileName = basename($source);
        $fileName = urldecode($fileName);
        $fileName = \URLify::filter($fileName, 250, '', true, true, false, false);

        return "/courses/$coursePath/document/$fileName";
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
