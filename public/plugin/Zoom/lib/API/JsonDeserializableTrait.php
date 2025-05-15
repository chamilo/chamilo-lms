<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Trait JsonDeserializableTrait.
 * Utility functions to help convert server-generated JSON to API class instances.
 */
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
        if (null === $object) {
            throw new Exception('Could not decode JSON: '.$json);
        }

        return static::fromObject($object);
    }

    /**
     * Builds a class instance from an already json-decoded object.
     *
     * @param object $object
     *
     * @throws Exception on unexpected object property
     *
     * @return static
     */
    public static function fromObject($object)
    {
        $instance = new static();
        static::recursivelyCopyObjectProperties($object, $instance);

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
    abstract public function itemClass($propertyName);

    /**
     * Initializes properties that can be calculated from json-decoded properties.
     *
     * Called at the end of method recursivelyCopyObjectProperties()
     * and indirectly at the end of static method fromJson().
     *
     * By default it does nothing.
     */
    public function initializeExtraProperties()
    {
        // default does nothing
    }

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
                        static::recursivelyCopyObjectProperties($value, $destination->$name);
                    } else {
                        throw new Exception("Source property $name is an object, which is not expected");
                    }
                } elseif (is_array($value)) {
                    if (is_array($destination->$name)) {
                        $itemClass = $destination->itemClass($name);
                        foreach ($value as $sourceItem) {
                            if ('string' === $itemClass) {
                                $destination->$name[] = $sourceItem;
                            } else {
                                $item = new $itemClass();
                                static::recursivelyCopyObjectProperties($sourceItem, $item);
                                $destination->$name[] = $item;
                            }
                        }
                    } else {
                        throw new Exception("Source property $name is an array, which is not expected");
                    }
                } else {
                    $destination->$name = $value;
                }
            } else {
                error_log("Source object has property $name, which was not expected: ".json_encode($source));
            }
        }
        $destination->initializeExtraProperties();
    }
}
