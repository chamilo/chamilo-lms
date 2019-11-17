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
     * @return mixed
     */
    public function transform(array $data)
    {
        list($content, $mCourseId) = array_values($data);

        $doc = new \DOMDocument();
        $doc->loadHTML(
            mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8')
        );

        /** @var \DOMElement $img */
        foreach ($doc->getElementsByTagName('img') as $img) {
            $source = \URLify::filter(
                $img->getAttribute('src'),
                250,
                '',
                true,
                true,
                false,
                false
            );

            $img->setAttribute('src' , $source);
        }

        $content = $doc->saveHTML();

        $cId = parent::transform([$mCourseId]);
        $courseInfo = api_get_course_info_by_id($cId);

        $newPath = "/courses/{$courseInfo['path']}/document";
        $content = str_replace('@@PLUGINFILE@@', $newPath, $content);

        return $content;
    }
}
