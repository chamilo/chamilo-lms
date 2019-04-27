<?php

namespace Knp\DoctrineBehaviors\DBAL\Types;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Knp\DoctrineBehaviors\ORM\Geocodable\Type\Point;

/**
 * Mapping type for spatial POINT objects
 */
class PointType extends Type
{
    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return 'point';
    }

    /**
     * Returns the SQL declaration snippet for a field of this type.
     *
     * @param array            $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform         The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'POINT';
    }

    /**
     * Converts SQL value to the PHP representation.
     *
     * @param string           $value    value in DB format
     * @param AbstractPlatform $platform DB platform
     *
     * @return Point
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return null;
        }

        if ($platform instanceof MySqlPlatform) {
            $data = sscanf($value, 'POINT(%f %f)');
        } else {
            $data = sscanf($value, "(%f,%f)");
        }

        return new Point($data[0], $data[1]);
    }

    /**
     * Converts PHP representation to the SQL value.
     *
     * @param Point            $value    specific point
     * @param AbstractPlatform $platform DB platform
     *
     * @return string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return null;
        }

        if ($platform instanceof MySqlPlatform) {
            $format = "POINT(%F %F)";
        } else {
            $format = "(%F, %F)";
        }

        return sprintf($format, $value->getLatitude(), $value->getLongitude());
    }

    /**
     * Does working with this column require SQL conversion functions?
     *
     * This is a metadata function that is required for example in the ORM.
     * Usage of {@link convertToDatabaseValueSQL} and
     * {@link convertToPHPValueSQL} works for any type and mostly
     * does nothing. This method can additionally be used for optimization purposes.
     *
     * @return boolean
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * Modifies the SQL expression (identifier, parameter) to convert to a database value.
     *
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        if ($platform instanceof MySqlPlatform) {
            return sprintf('PointFromText(%s)', $sqlExpr);
        } else {
            return parent::convertToDatabaseValueSQL($sqlExpr, $platform);
        }
    }

    /**
     * @param string                                    $sqlExpr
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        if ($platform instanceof MySqlPlatform) {
            return sprintf('AsText(%s)', $sqlExpr);
        } else {
            return parent::convertToPHPValueSQL($sqlExpr, $platform);
        }
    }
}
