<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KyoceraRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ISO extends AbstractTag
{

    protected $Id = 52;

    protected $Name = 'ISO';

    protected $FullName = 'KyoceraRaw::Main';

    protected $GroupName = 'KyoceraRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KyoceraRaw';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'ISO';

    protected $local_g2 = 'Image';

    protected $flag_Permanent = true;

    protected $Values = array(
        7 => array(
            'Id' => 7,
            'Label' => 25,
        ),
        8 => array(
            'Id' => 8,
            'Label' => 32,
        ),
        9 => array(
            'Id' => 9,
            'Label' => 40,
        ),
        10 => array(
            'Id' => 10,
            'Label' => 50,
        ),
        11 => array(
            'Id' => 11,
            'Label' => 64,
        ),
        12 => array(
            'Id' => 12,
            'Label' => 80,
        ),
        13 => array(
            'Id' => 13,
            'Label' => 100,
        ),
        14 => array(
            'Id' => 14,
            'Label' => 125,
        ),
        15 => array(
            'Id' => 15,
            'Label' => 160,
        ),
        16 => array(
            'Id' => 16,
            'Label' => 200,
        ),
        17 => array(
            'Id' => 17,
            'Label' => 250,
        ),
        18 => array(
            'Id' => 18,
            'Label' => 320,
        ),
        19 => array(
            'Id' => 19,
            'Label' => 400,
        ),
    );

}
