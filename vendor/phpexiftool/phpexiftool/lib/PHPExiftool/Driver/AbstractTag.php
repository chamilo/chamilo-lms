<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver;

use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * Abstract Tag object
 *
 * @ExclusionPolicy("all")
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
abstract class AbstractTag implements TagInterface
{

    protected $Id;
    protected $Name;
    protected $Type;
    protected $Description;
    protected $Values;
    protected $FullName;
    protected $GroupName;
    protected $g0;
    protected $g1;
    protected $g2;
    protected $MinLength = 0;
    protected $MaxLength;
    protected $Writable       = false;
    protected $flag_Avoid     = false;
    protected $flag_Binary    = false;
    protected $flag_Permanent = false;
    protected $flag_Protected = false;
    protected $flag_Unsafe    = false;
    protected $flag_List      = false;
    protected $flag_Mandatory = false;
    protected $flag_Bag       = false;
    protected $flag_Seq       = false;
    protected $flag_Alt       = false;

    /**
     * Return Tag Id - Tag dependant
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * Return the tag name
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * A small string about the Tag
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * An array of available values for this tag
     * Other values should not be allowed
     *
     * @VirtualProperty
     *
     * @return array
     */
    public function getValues()
    {
        return $this->Values;
    }

    /**
     * Returns true if the Tag handles list values
     *
     * @VirtualProperty
     *
     * @return boolean
     */
    public function isMulti()
    {
        return $this->flag_List;
    }

    /**
     * Returns true if the value is binary
     *
     * @VirtualProperty
     *
     * @return type
     */
    public function isBinary()
    {
        return $this->flag_Binary;
    }

    /**
     * Returns tag group name
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->GroupName;
    }

    /**
     * Returns true if the value can be written in the tag
     *
     * @VirtualProperty
     *
     * @return type
     */
    public function isWritable()
    {
        return $this->Writable;
    }

    /**
     * Return the tagname path ; ie GroupName:Name
     *
     * @VirtualProperty
     *
     * @return type
     */
    public function getTagname()
    {
        return $this->GroupName . ':' . $this->Name;
    }

    /**
     *
     * @VirtualProperty
     *
     * @return integer
     */
    public function getMinLength()
    {
        return $this->MinLength;
    }

    /**
     *
     * @VirtualProperty
     *
     * @return integer
     */
    public function getMaxLength()
    {
        return $this->MaxLength;
    }

    /**
     * Return the tagname
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTagname();
    }

}
