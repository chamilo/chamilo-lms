<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class YCbCrSubSampling extends AbstractTag
{

    protected $Id = 530;

    protected $Name = 'YCbCrSubSampling';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Y Cb Cr Sub Sampling';

    protected $flag_Unsafe = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '1 1' => array(
            'Id' => '1 1',
            'Label' => 'YCbCr4:4:4 (1 1)',
        ),
        '1 2' => array(
            'Id' => '1 2',
            'Label' => 'YCbCr4:4:0 (1 2)',
        ),
        '1 4' => array(
            'Id' => '1 4',
            'Label' => 'YCbCr4:4:1 (1 4)',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => 'YCbCr4:2:2 (2 1)',
        ),
        '2 2' => array(
            'Id' => '2 2',
            'Label' => 'YCbCr4:2:0 (2 2)',
        ),
        '2 4' => array(
            'Id' => '2 4',
            'Label' => 'YCbCr4:2:1 (2 4)',
        ),
        '4 1' => array(
            'Id' => '4 1',
            'Label' => 'YCbCr4:1:1 (4 1)',
        ),
        '4 2' => array(
            'Id' => '4 2',
            'Label' => 'YCbCr4:1:0 (4 2)',
        ),
    );

}
