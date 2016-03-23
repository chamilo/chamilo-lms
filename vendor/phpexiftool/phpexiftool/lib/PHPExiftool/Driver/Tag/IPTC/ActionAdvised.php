<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ActionAdvised extends AbstractTag
{

    protected $Id = 42;

    protected $Name = 'ActionAdvised';

    protected $FullName = 'IPTC::ApplicationRecord';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'digits';

    protected $Writable = true;

    protected $Description = 'Action Advised';

    protected $MaxLength = 2;

    protected $Values = array(
        01 => array(
            'Id' => 01,
            'Label' => 'Object Kill',
        ),
        02 => array(
            'Id' => 02,
            'Label' => 'Object Replace',
        ),
        03 => array(
            'Id' => 03,
            'Label' => 'Object Append',
        ),
        04 => array(
            'Id' => 04,
            'Label' => 'Object Reference',
        ),
        '' => array(
            'Id' => '',
            'Label' => '',
        ),
    );

}
