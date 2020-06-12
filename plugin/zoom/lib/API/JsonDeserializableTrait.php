<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

trait JsonDeserializableTrait
{
    /**
     * Builds a class instance from the Json description of the object.
     *
     * @param string $json
     *
     * @throws Exception on JSON-decode error or unexpected object property
     *
     * @return static
     */
    public static function fromJson($json)
    {
        if (empty($json)) {
            throw new Exception('Cannot JSON-decode empty string');
        }
        $object = json_decode($json);
        if (is_null($object)) {
            throw new Exception('Could not decode JSON: '.$json);
        }

        $instance = new static();
        self::recursivelyCopyObjectProperties($object, $instance);

        return $instance;
    }

    /**
     * Returns the class name of the items to be found in the named array property.
     *
     * To override in classes that have a property of type array
     *
     * @param string $propertyName array property name
     *
     * @throws Exception if not implemented for this propertyName
     *
     * @return string class name of the items to be found in the named array property
     */
    abstract protected function itemClass($propertyName);

    /**
     * Copies values from another object properties to an instance, recursively.
     *
     * @param object $source      source object
     * @param object $destination specific class instance, with already initialized properties
     *
     * @throws Exception when the source object has an unexpected property
     */
    protected static function recursivelyCopyObjectProperties($source, &$destination)
    {
        foreach (get_object_vars($source) as $name => $value) {
            if (property_exists($destination, $name)) {
                if (is_object($value)) {
                    if (is_object($destination->$name)) {
                        self::recursivelyCopyObjectProperties($value, $destination->$name);
                    } else {
                        throw new Exception("Source property $name is an object, which is not expected");
                    }
                } elseif (is_array($value)) {
                    if (is_array($destination->$name)) {
                        foreach ($value as $sourceItem) {
                            $itemClass = $destination->itemClass($name);
                            $item = new $itemClass();
                            self::recursivelyCopyObjectProperties($sourceItem, $item);
                            $destination->$name[] = $item;
                        }
                    } else {
                        throw new Exception("Source property $name is an array, which is not expected");
                    }
                } else {
                    $destination->$name = $value;
                }
            } else {
                throw new Exception("Source object has property $name, which was not expected.");
            }
        }
    }
}
