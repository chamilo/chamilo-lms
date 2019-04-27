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

/**
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
interface TagInterface
{

    /**
     * Return Tag Id - Tag dependant
     *
     * @return string
     */
    public function getId();

    /**
     * Return the tag name
     *
     * @return string
     */
    public function getName();

    /**
     * A small string about the Tag
     *
     * @return string
     */
    public function getDescription();

    /**
     * An array of available values for this tag
     * Other values should not be allowed
     *
     * @return array
     */
    public function getValues();

    /**
     * Returns true if the Tag handles list values
     *
     * @return boolean
     */
    public function isMulti();

    /**
     * Returns true if the value is binary
     *
     * @return type
     */
    public function isBinary();

    /**
     * Returns tag group name
     *
     * @return string
     */
    public function getGroupName();

    /**
     * Returns true if the value can be written in the tag
     *
     * @return type
     */
    public function isWritable();

    /**
     * Return the tagname path ; ie GroupName:Name
     *
     * @return type
     */
    public function getTagname();

    public function getMinLength();

    public function getMaxLength();
}
