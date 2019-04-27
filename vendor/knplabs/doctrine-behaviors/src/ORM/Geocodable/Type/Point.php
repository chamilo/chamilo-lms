<?php

namespace Knp\DoctrineBehaviors\ORM\Geocodable\Type;

/**
 * Point object for spatial mapping
 */
class Point
{
    private $latitude;
    private $longitude;

    /**
     * Initializes point.
     *
     * @param float|integer $latitude
     * @param float|integer $longitude
     */
    public function __construct($latitude, $longitude)
    {
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Creates new Point from array.
     *
     * @param array $array either hash or array of lat, long
     *
     * @return Point
     */
    public static function fromArray(array $array)
    {
        if (isset($array['latitude'])) {
            return new self($array['latitude'], $array['longitude']);
        } else {
            return new self($array[0], $array[1]);
        }
    }

    /**
     * Creates new Point from string.
     *
     * @param string $string string in (%f,%f) format
     *
     * @return Point
     */
    public static function fromString($string)
    {
        return self::fromArray(sscanf($string, '(%f,%f)'));
    }

    /**
     * Returns Point latitude.
     *
     * @return float|integer
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Returns Point longitude.
     *
     * @return float|integer
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Returns string representation for Point in (%f,%f) format.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('(%F,%F)', $this->latitude, $this->longitude);
    }

    public function isEmpty()
    {
        return empty($this->latitude) && empty($this->longitude);
    }
}
