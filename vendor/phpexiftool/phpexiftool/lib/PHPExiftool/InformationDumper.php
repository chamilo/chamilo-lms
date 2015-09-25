<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use PHPExiftool\Exception\InvalidArgumentException;

class InformationDumper
{
    /**
     * For use with list option
     */
    const LISTTYPE_WRITABLE          = 'w';
    /**
     * For use with list option
     */
    const LISTTYPE_SUPPORTED_FILEEXT = 'f';
    /**
     * For use with list option
     */
    const LISTTYPE_WRITABLE_FILEEXT  = 'wf';
    /**
     * For use with list option
     */
    const LISTTYPE_SUPPORTED_XML     = 'x';
    /**
     * For use with list option
     */
    const LISTTYPE_DELETABLE_GROUPS  = 'd';
    /**
     * For use with list option
     */
    const LISTTYPE_GROUPS            = 'g';

    private $exiftool;

    public function __construct(Exiftool $exiftool)
    {
        $this->exiftool = $exiftool;
    }

    /**
     * Return the result of a Exiftool -list* command
     *
     * @see http://www.sno.phy.queensu.ca/~phil/exiftool/exiftool_pod.html#item__2dlist_2c__2dlistw_2c__2dlistf_2c__2dlistr_2c__2d
     * @param  string     $type One of the LISTTYPE_* constants
     * @return type
     * @throws \Exception
     */
    public function listDatas($type = self::LISTTYPE_SUPPORTED_XML)
    {
        $available = array(
            self::LISTTYPE_WRITABLE, self::LISTTYPE_SUPPORTED_FILEEXT
            , self::LISTTYPE_WRITABLE_FILEEXT, self::LISTTYPE_SUPPORTED_XML
            , self::LISTTYPE_DELETABLE_GROUPS, self::LISTTYPE_GROUPS,
        );

        if ( ! in_array($type, $available)) {
            throw new InvalidArgumentException('Unknown list attribute');
        }

        return $this->exiftool->executeCommand('-f -list' . $type);
    }
}
