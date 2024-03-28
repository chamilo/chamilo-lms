<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class Language.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class Language implements TransformPropertyInterface
{
    /**
     * @var array
     */
    private $languages = [
        'en' => 'english',
        'fr' => 'french',
        'es' => 'spanish',
    ];

    /**
     * @return string
     */
    public function transform(array $data)
    {
        $language = current($data);

        if (array_key_exists($language, $this->languages)) {
            return $this->languages[$language];
        }

        return $this->languages['en'];
    }
}
