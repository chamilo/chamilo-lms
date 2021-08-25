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
class PentaxImageSize extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'PentaxImageSize';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Pentax Image Size';

    protected $local_g2 = 'Image';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '640x480',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Full',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1024x768',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1280x960',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1600x1200',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '2048x1536',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '2560x1920 or 2304x1728',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '3072x2304',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '3264x2448',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '320x240',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '2288x1712',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '2592x1944',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '2304x1728 or 2592x1944',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '3056x2296',
        ),
        25 => array(
            'Id' => 25,
            'Label' => '2816x2212 or 2816x2112',
        ),
        27 => array(
            'Id' => 27,
            'Label' => '3648x2736',
        ),
        29 => array(
            'Id' => 29,
            'Label' => '4000x3000',
        ),
        30 => array(
            'Id' => 30,
            'Label' => '4288x3216',
        ),
        31 => array(
            'Id' => 31,
            'Label' => '4608x3456',
        ),
        129 => array(
            'Id' => 129,
            'Label' => '1920x1080',
        ),
        135 => array(
            'Id' => 135,
            'Label' => '4608x2592',
        ),
        257 => array(
            'Id' => 257,
            'Label' => '3216x3216',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => '2304x1728',
        ),
        '4 0' => array(
            'Id' => '4 0',
            'Label' => '1600x1200',
        ),
        '5 0' => array(
            'Id' => '5 0',
            'Label' => '2048x1536',
        ),
        '8 0' => array(
            'Id' => '8 0',
            'Label' => '2560x1920',
        ),
        '32 2' => array(
            'Id' => '32 2',
            'Label' => '960x640',
        ),
        '33 2' => array(
            'Id' => '33 2',
            'Label' => '1152x768',
        ),
        '34 2' => array(
            'Id' => '34 2',
            'Label' => '1536x1024',
        ),
        '35 1' => array(
            'Id' => '35 1',
            'Label' => '2400x1600',
        ),
        '36 0' => array(
            'Id' => '36 0',
            'Label' => '3008x2008 or 3040x2024',
        ),
        '37 0' => array(
            'Id' => '37 0',
            'Label' => '3008x2000',
        ),
    );

}
