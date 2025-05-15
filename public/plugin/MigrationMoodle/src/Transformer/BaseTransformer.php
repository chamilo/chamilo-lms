<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformerInterface;
use Chamilo\PluginBundle\MigrationMoodle\Transformer\Property\Copy;

class BaseTransformer implements TransformerInterface
{
    /**
     * @var array
     */
    private $map;

    /**
     * BaseTransformer constructor.
     */
    public function __construct(array $configuration)
    {
        $this->map = $configuration['map'];
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function transform(array $sourceData)
    {
        $incomingResult = [];

        foreach ($this->map as $incomingProperty => $sourceProperty) {
            if (is_array($sourceProperty)) {
                $transformerClass = $sourceProperty['class'];
                /** @var TransformerInterface $transformer */
                $transformer = new $transformerClass();
                $sourceProperties = $sourceProperty['properties'];
            } else {
                $transformer = new Copy();
                $sourceProperties = [$sourceProperty];
            }

            $data = [];

            foreach ($sourceProperties as $sourcePropertyName) {
                $data[$sourcePropertyName] = $sourceData[$sourcePropertyName];
            }

            $incomingResult[$incomingProperty] = $transformer->transform($data);
        }

        return $incomingResult;
    }
}
