<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HighLowKeyAdj extends AbstractTag
{

    protected $Id = 108;

    protected $Name = 'HighLowKeyAdj';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'High/Low Key Adj';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '-1 0' => array(
            'Id' => '-1 0',
            'Label' => '-1',
        ),
        '-2 0' => array(
            'Id' => '-2 0',
            'Label' => '-2',
        ),
        '-3 0' => array(
            'Id' => '-3 0',
            'Label' => '-3',
        ),
        '-4 0' => array(
            'Id' => '-4 0',
            'Label' => '-4',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 0,
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 1,
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 2,
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 3,
        ),
        '4 0' => array(
            'Id' => '4 0',
            'Label' => 4,
        ),
    );

}
